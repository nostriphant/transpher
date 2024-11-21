<?php


namespace nostriphant\Transpher\Relay\Store;

use function \Functional\select;
use nostriphant\Transpher\Nostr\Subscription;

trait Memory {

    public function __construct(private array $events) {

    }

    public function __invoke(Subscription $subscription): array {
        return select($this->events, $subscription);
    }

    public function offsetExists(mixed $offset): bool {
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
