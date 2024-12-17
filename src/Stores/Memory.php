<?php


namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;

final class Memory implements \nostriphant\Transpher\Relay\Store {

    public function __construct(private array $events) {
        
    }

    #[\Override]
    public function __invoke(array ...$filter_prototypes): Results {
        $subscription = new Subscription($filter_prototypes, \nostriphant\Transpher\Relay\Condition::class);
        return new Results(function () use ($subscription) {
                    yield from array_filter($this->events, $subscription);
                });
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        return isset($this->events[$offset]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): ?Event {
        return $this->events[$offset];
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void {
        if (isset($offset)) {
            $this->events[$offset] = $value;
        } else {
            $this->events[] = $value;
        }
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        unset($this->events[$offset]);
    }

    #[\Override]
    public function count(): int {
        return count($this->events);
    }

    #[\Override]
    public function getIterator(): \Traversable {
        yield from $this->events;
    }

}
