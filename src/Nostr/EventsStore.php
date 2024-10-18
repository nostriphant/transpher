<?php


namespace rikmeijer\Transpher\Nostr;

use rikmeijer\Transpher\Nostr\Message\Factory;
use function \Functional\select,
             \Functional\map,
             \Functional\partial_left;

trait EventsStore {

    private array $events = [];

    public function __invoke(Filters $subscription): callable {
        return fn(string $subscriptionId) => map(select($this->events, $subscription), partial_left([Factory::class, 'requestedEvent'], $subscriptionId));
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
}
