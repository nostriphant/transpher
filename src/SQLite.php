<?php

namespace nostriphant\Transpher;

use nostriphant\Transpher\Nostr\Subscription;

readonly class SQLite implements Relay\Store {

    public function __construct(private \SQLite3 $database) {
        $this->database->querySingle("CREATE TABLE IF NOT EXISTS event ("
                . "id INTEGER PRIMARY KEY ASC,"
                . "pubkey INTEGER,"
                . "created_at INTEGER,"
                . "kind INTEGER,"
                . "content TEXT,"
                . "sig INTEGER"
                . ")");

        $this->database->querySingle("CREATE TABLE IF NOT EXISTS tag ("
                . "id INTEGER PRIMARY KEY AUTOINCREMENT,"
                . "event_id INTEGER,"
                . "name TEXT"
                . ")");
    }

    public function __invoke(Subscription $subscription): array {
        return select($this->events, $subscription);
    }

    public function offsetExists(mixed $offset): bool {
        //return $this->database->q

        return isset($this->events[$offset]);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->events[$offset];
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
