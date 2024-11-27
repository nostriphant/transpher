<?php

namespace nostriphant\Transpher;

use nostriphant\Transpher\Nostr\Subscription;

readonly class SQLite implements Relay\Store {

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

    public function __invoke(Subscription $subscription): array {
        return select($this->events, $subscription);
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

    public function offsetGet(mixed $offset): mixed {
        $event = $this->fetchEventArray($offset);
        $query = $this->database->prepare("SELECT tag.id, tag.name, tag_value.position, tag_value.value "
                . "FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id "
                . "WHERE tag.event_id=:event_id");
        $query->bindValue('event_id', $offset);
        $tag_result = $query->execute();

        $tags = [];
        while ($tag = $tag_result->fetchArray(SQLITE3_ASSOC)) {
            $tags[$tag['id']] = ($tags[$tag['id']] ?? []) + [
                0 => $tag['name'],
                $tag['position'] => $tag['value']
            ];
        }
        $event['tags'] = array_values($tags);
        return \nostriphant\NIP01\Event::__set_state($event);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        if (!$value instanceof \nostriphant\NIP01\Event) {
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
        return count($this->events);
    }
}
