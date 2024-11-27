<?php

namespace nostriphant\Transpher;

use nostriphant\Transpher\Nostr\Subscription;

readonly class SQLite implements Relay\Store {

    public function __construct(private \SQLite3 $database) {
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
        if (isset($offset)) {
            $this->events[$offset] = $value;
        } else {
            $this->events[] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->events[$offset]);
    }

    public function count(): int {
        return count($this->events);
    }
}
