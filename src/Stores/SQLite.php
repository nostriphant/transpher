<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;
use nostriphant\Transpher\Relay\Conditions;

readonly class SQLite implements \nostriphant\Transpher\Relay\Store {

    public function __construct(private \SQLite3 $database) {
        $this->database->exec("PRAGMA foreign_keys = ON");
        $this->database->exec("CREATE TABLE IF NOT EXISTS event ("
                . "id TEXT PRIMARY KEY ASC,"
                . "pubkey TEXT,"
                . "created_at INTEGER,"
                . "kind INTEGER,"
                . "content TEXT,"
                . "sig TEXT"
                . ")");

        $this->database->exec("CREATE TABLE IF NOT EXISTS tag ("
                . "id INTEGER PRIMARY KEY AUTOINCREMENT,"
                . "event_id TEXT REFERENCES event (id) ON DELETE CASCADE ON UPDATE CASCADE,"
                . "name TEXT"
                . ")");

        $this->database->exec("CREATE TABLE IF NOT EXISTS tag_value ("
                . "id INTEGER PRIMARY KEY AUTOINCREMENT,"
                . "position INTEGER,"
                . "tag_id INTEGER REFERENCES tag (id) ON DELETE CASCADE ON UPDATE CASCADE,"
                . "value TEXT,"
                . "UNIQUE (tag_id, position) ON CONFLICT FAIL"
                . ")");

        $this->database->exec("CREATE TRIGGER IF NOT EXISTS auto_increment_position_trigger "
                . "AFTER INSERT ON tag_value WHEN new.position IS NULL BEGIN"
                . "    UPDATE tag_value"
                . "    SET position = (SELECT IFNULL(MAX(position), 0) + 1 FROM tag_value WHERE tag_id = new.tag_id)"
                . "    WHERE id = new.id;"
                . "END;"
        );
    }

    public function __invoke(Subscription $subscription): \Generator {
        $to = new Conditions(SQLite\Condition::class);
        $filters = array_map(fn(array $filter_prototype) => SQLite\Filter::fromPrototype(...$to($filter_prototype)), $subscription->filter_prototypes);

        $query_prototype = array_reduce($filters, fn(array $query_prototype, SQLite\Filter $filter) => $filter($query_prototype), [
            'where' => [],
            'limit' => null
        ]);

        $parameters = [];
        $where = array_reduce($query_prototype['where'], function (array $where, array $condition) use (&$parameters) {
            $where[] = array_shift($condition);
            $parameters = array_merge($parameters, $condition);
            return $where;
        }, []);

        $query = "SELECT id, pubkey, created_at, kind, content, sig "
                . "FROM event "
                . "WHERE (" . implode(') AND (', $where) . ")"
                . ($query_prototype['limit'] !== null ? "LIMIT " . $query_prototype['limit'] : "");
        $statement = $this->database->prepare($query);
        array_walk($parameters, function (mixed $parameter, int $position) use ($statement) {
            $statement->bindValue($position + 1, $parameter);
        });

        $result = $statement->execute();
        if ($result === false) {
            throw new \Exception($this->database->lastErrorMsg());
        }

        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $data['tags'] = $this->collectTags($data['id']);
            yield new Event(...$data);
        }
    }

    private function fetchEventArray(string $event_id): array {
        $query = $this->database->prepare("SELECT id, pubkey, created_at, kind, content, sig FROM event WHERE id=:event_id LIMIT 1");
        $query->bindValue('event_id', $event_id);
        $result = $query->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    public function offsetExists(mixed $offset): bool {
        return $this->fetchEventArray($offset)['id'] === $offset;
    }

    private function collectTags(string $event_id) {
        $query = $this->database->prepare("SELECT tag.id, tag.name, tag_value.position, tag_value.value "
                . "FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id "
                . "WHERE tag.event_id=:event_id");
        $query->bindValue('event_id', $event_id);
        $tag_result = $query->execute();

        $tags = [];
        while ($tag = $tag_result->fetchArray(SQLITE3_ASSOC)) {
            $tags[$tag['id']] = ($tags[$tag['id']] ?? []) + [
                0 => $tag['name'],
                $tag['position'] => $tag['value']
            ];
        }
        return array_values($tags);
    }

    public function offsetGet(mixed $offset): Event {
        $event = $this->fetchEventArray($offset);
        $event['tags'] = $this->collectTags($offset);
        return Event::__set_state($event);
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void {
        if (!$value instanceof Event) {
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
    }

    public function offsetUnset(mixed $offset): void {
        $query = $this->database->prepare("DELETE FROM event WHERE id = :event_id");
        $query->bindValue('event_id', $offset);
        $query->execute();
    }

    public function count(): int {
        return $this->database->querySingle("SELECT COUNT(id) FROM event");
    }
}
