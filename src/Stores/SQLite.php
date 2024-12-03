<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;
use nostriphant\Transpher\Relay\Conditions;

readonly class SQLite implements \nostriphant\Transpher\Relay\Store {

    const VERSION = '20241202';

    public function __construct(private \SQLite3 $database, private Subscription $whitelist, private \Psr\Log\LoggerInterface $log) {
        $this->database->exec("PRAGMA foreign_keys = ON");
        $this->log->debug('Enabled foreign keys in database');

        $this->database->exec("CREATE TABLE IF NOT EXISTS event ("
                . "id TEXT PRIMARY KEY ASC,"
                . "pubkey TEXT,"
                . "created_at INTEGER,"
                . "kind INTEGER,"
                . "content TEXT,"
                . "sig TEXT"
                . ")");
        $this->log->debug('Table event created (if it did not already exist)');
        if (self::retrieveVersion($this->database) < self::VERSION) {
            $this->database->exec('ALTER TABLE event ADD COLUMN tags_json TEXT');
        }

        $this->database->exec("CREATE TABLE IF NOT EXISTS tag ("
                . "id INTEGER PRIMARY KEY AUTOINCREMENT,"
                . "event_id TEXT REFERENCES event (id) ON DELETE CASCADE ON UPDATE CASCADE,"
                . "name TEXT"
                . ")");
        $this->log->debug('Table tag created (if it did not already exist)');

        $this->database->exec("CREATE TABLE IF NOT EXISTS tag_value ("
                . "id INTEGER PRIMARY KEY AUTOINCREMENT,"
                . "position INTEGER,"
                . "tag_id INTEGER REFERENCES tag (id) ON DELETE CASCADE ON UPDATE CASCADE,"
                . "value TEXT,"
                . "UNIQUE (tag_id, position) ON CONFLICT FAIL"
                . ")");
        $this->log->debug('Table tag_value created (if it did not already exist)');

        $this->database->exec("CREATE TRIGGER IF NOT EXISTS auto_increment_position_trigger "
                . "AFTER INSERT ON tag_value WHEN new.position IS NULL BEGIN"
                . "    UPDATE tag_value"
                . "    SET position = (SELECT IFNULL(MAX(position), 0) + 1 FROM tag_value WHERE tag_id = new.tag_id)"
                . "    WHERE id = new.id;"
                . "END;"
        );
        $this->log->debug('Trigger auto_increment_position_trigger created (if it did not already exist)');

        $this->database->exec("CREATE VIEW IF NOT EXISTS event_tag_json AS "
                . "SELECT "
                . "    tag.event_id, "
                . "    tag.name, "
                . "    json_insert(json_group_array(tag_value.value), '$[#]', tag.name) AS json"
                . " FROM tag "
                . " LEFT JOIN tag_value ON tag.id = tag_value.tag_id "
                . " GROUP BY tag.name"
                . " ORDER BY tag_value.position ASC"
        );
        $this->log->debug('View event_tag_json created (if it did not already exist)');

        $this->database->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE tags_json IS NULL");
        $this->log->debug('Updated missing tags_json values');

        $this->database->exec('PRAGMA user_version = "' . self::VERSION . '"');

        if ($whitelist->enabled) {
            list($select, $parameters) = self::generateSelectQuery($whitelist, ["event.id"]);
            $statement = $this->database->prepare("DELETE "
                    . "FROM event "
                    . "WHERE event.id NOT IN (" . $select . ") ");
            if ($statement === false) {
                $this->log->error('Query failed: ' . $this->database->lastErrorMsg());
                return;
            }

            $parameters($statement);
            $result = $statement->execute();
            if ($result === false) {
                $this->log->error('Cleanup query failed: ' . $this->database->lastErrorMsg());
            }
        }
    }

    static function retrieveVersion(\SQLite3 $database): string {
        return $database->querySingle('PRAGMA user_version');
    }

    static function transformSubscriptionToQueryConditions(Subscription $subscription) {
        $to = new Conditions(SQLite\Condition::class);
        $filters = array_map(fn(array $filter_prototype) => SQLite\Filter::fromPrototype(...$to($filter_prototype)), $subscription->filter_prototypes);
        return array_reduce($filters, fn(array $query_prototype, SQLite\Filter $filter) => $filter($query_prototype), [
            'where' => [],
            'limit' => null
        ]);
    }

    static function extractConditionsAndParametersFromQueryPrototype(array $query_prototype) {
        $parameters = [];
        $where = array_reduce($query_prototype['where'], function (array $where, array $condition) use (&$parameters) {
            $where[] = array_shift($condition);
            $parameters = array_merge($parameters, $condition);
            return $where;
        }, []);

        return [$where, fn(\SQLite3Stmt $statement) => array_walk($parameters, function (mixed $parameter, int $position) use ($statement) {
            $statement->bindValue($position + 1, $parameter);
        })];
    }

    static function generateSelectQuery(Subscription $subscription, array $fields) {
        $query_prototype = self::transformSubscriptionToQueryConditions($subscription);
        list($where, $parameters) = self::extractConditionsAndParametersFromQueryPrototype($query_prototype);
        return [
            "SELECT " . implode(',', $fields) . " FROM event "
            . "LEFT JOIN tag ON tag.event_id = event.id "
                . "LEFT JOIN tag_value ON tag.id = tag_value.tag_id "
                . "WHERE (" . implode(') AND (', $where) . ") "
                . 'GROUP BY event.id '
                . ($query_prototype['limit'] !== null ? "LIMIT " . $query_prototype['limit'] : ""), $parameters
        ];
    }

    private function queryEvents(Subscription $subscription): \Generator {
        list($query, $parameters) = self::generateSelectQuery($subscription, ["event.id", "pubkey", "created_at", "kind", "content", "sig", "tags_json"]);
        $statement = $this->database->prepare($query);
        if ($statement === false) {
            $this->log->error('Query failed: ' . $this->database->lastErrorMsg());
            return;
        }

        $parameters($statement);

        $result = $statement->execute();
        if ($result === false) {
            $this->log->error('Query failed: ' . $this->database->lastErrorMsg());
            return;
        }

        $count = 0;
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $data['tags'] = json_decode('[' . $data['tags_json'] . ']') ?? [];
            array_walk($data['tags'], fn(array &$tag) => array_unshift($tag, array_pop($tag)));
            unset($data['tags_json']);
            yield new Event(...$data);
            $count++;
        }

        $this->log->debug('Yielded ' . $count . ' events.');
    }

    #[\Override]
    public function __invoke(Subscription $subscription): \Generator {
        $this->log->debug('Filtering using ' . count($subscription->filter_prototypes) . ' filters.');
        yield from $this->queryEvents($subscription);
    }

    private function fetchEventArray(string $event_id): ?Event {
        $this->log->debug('Fetching event ' . $event_id . '.');
        $events = iterator_to_array($this->queryEvents(Subscription::make([
                            'ids' => [$event_id],
                            'limit' => 1
        ])));
        return count($events) > 0 ? $events[0] : null;
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        $this->log->debug('Does event ' . $offset . ' exist?');
        $event = $this->fetchEventArray($offset);
        return $event !== null ? $event->id === $offset : false;
    }

    #[\Override]
    public function offsetGet(mixed $offset): ?Event {
        $this->log->debug('Getting event ' . $offset);
        return $this->fetchEventArray($offset);
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void {
        $this->log->debug('Setting event ' . $offset);
        if (!$value instanceof Event) {
            return;
        } elseif (call_user_func($this->whitelist, $value) === false) {
            return;
        }

        $query = $this->database->prepare("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                . ":id,"
                . ":pubkey,"
                . ":created_at,"
                . ":kind,"
                . ":content,"
                . ":sig"
                . ")");
        $event = get_object_vars($value);
        $tags = [];
        foreach ($event as $property => $value) {
            if ($property === 'tags') {
                foreach ($value as $event_tag) {
                    $tag = [
                        'query' => $this->database->prepare("INSERT INTO tag (event_id, name) VALUES (:event_id, :name)"),
                        'values' => []
                    ];
                    $tag['query']->bindValue('event_id', $event['id']);
                    $tag['query']->bindValue('name', array_shift($event_tag));

                    foreach ($event_tag as $position => $event_tag_value) {
                        $tag_value_query = $this->database->prepare("INSERT INTO tag_value (tag_id, position, value) VALUES (:tag_id, :position, :value)");
                        $tag_value_query->bindValue('position', $position + 1);
                        $tag_value_query->bindValue('value', $event_tag_value);
                        $tag['values'][] = $tag_value_query;
                    }

                    $tags[] = $tag;
                }
                
            } else {
                $query->bindValue($property, $value);
            }
        }

        if ($query->execute() !== false) {
            foreach ($tags as $tag) {
                if ($tag['query']->execute() !== false) {
                    $tag_id = $this->database->lastInsertRowID();
                    foreach ($tag['values'] as $value) {
                        $value->bindValue('tag_id', $tag_id);
                        $value->execute();
                    }
                }
            }
        }

        $update = $this->database->prepare("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = ?");
        $update->bindValue(1, $event['id']);
        $update->execute();
        $this->log->debug('Updated event tags-json');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        $this->log->debug('Deleting event ' . $offset);
        $query = $this->database->prepare("DELETE FROM event WHERE id = :event_id");
        $query->bindValue('event_id', $offset);
        $query->execute();
    }

    #[\Override]
    public function count(): int {
        $this->log->debug('Counting events');
        return $this->database->querySingle("SELECT COUNT(id) FROM event");
    }
}
