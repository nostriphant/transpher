<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;

readonly class SQLite implements \nostriphant\Transpher\Relay\Store {

    use MemoryWrapper {
        __construct As MW_Construct;
        offsetSet As MW_offsetSet;
        offsetUnset As MW_offsetUnset;
    }

    public \nostriphant\Transpher\Stores\Housekeeper $housekeeper;

    public function __construct(public \SQLite3 $database, public array $whitelist_prototypes) {
        $structure = new SQLite\Structure();
        $structure($database);
        if (Subscription::disabled($whitelist_prototypes) === false) {
            $this->housekeeper = new SQLite\Housekeeper($this);
        } else {
            $this->housekeeper = new NullHousekeeper();
        }

        $this()(Results::copyTo($events));
        $this->MW_Construct($events, $whitelist_prototypes);
    }

    public function query(Subscription $conditions, string ...$fields): SQLite\Statement {
        $query_prototype = $conditions([
            'where' => [],
            'limit' => null
        ]);

        $query = "SELECT " . implode(',', $fields) . " FROM event "
                . "LEFT JOIN tag ON tag.event_id = event.id "
                . "LEFT JOIN tag_value ON tag.id = tag_value.tag_id ";

        $parameters = [];
        if (isset($query_prototype['where'])) {
            list($where, $parameters) = array_reduce($query_prototype['where'], function (array $return, array $condition) {
                $return[0][] = array_shift($condition);
                $return[1] = array_merge($return[1], $condition);
                return $return;
            }, [[], []]);
            $query .= "WHERE (" . implode(') AND (', $where) . ") ";
        }

        $query .= 'GROUP BY event.id '
                . (isset($query_prototype['limit']) ? "LIMIT " . $query_prototype['limit'] : "");
        return new SQLite\Statement($query, $parameters);
    }

    private function queryEvent(string $event_id): Results {
        return $this([
            'ids' => [$event_id],
            'limit' => 1
        ]);
    }

    #[\Override]
    public function __invoke(array ...$filter_prototypes): Results {
        return $this->query(new Subscription($filter_prototypes, SQLite\Condition::class), "event.id", "pubkey", "created_at", "kind", "content", "sig", "tags_json")($this->database);
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        return $this->offsetGet($offset) !== null;
    }

    #[\Override]
    public function offsetGet(mixed $offset): ?Event {
        $event = null;
        $this->queryEvent($offset)(function (Event $found_event) use (&$event) {
            $event = $found_event;
        });
        return $event;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void {
        if (!$value instanceof Event) {
            return;
        }

        $whitelist = new Subscription($this->whitelist_prototypes, \nostriphant\Transpher\Relay\Condition::class);
        if (call_user_func($whitelist, $value) === false) {
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
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        $query = $this->database->prepare("DELETE FROM event WHERE id = :event_id");
        $query->bindValue('event_id', $offset);
        $query->execute();
    }

    #[\Override]
    public function count(): int {
        return $this->database->querySingle("SELECT COUNT(id) FROM event");
    }
}
