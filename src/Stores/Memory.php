<?php


namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;

final class Memory implements \nostriphant\Transpher\Relay\Store {

    readonly public Subscription $whitelist;
    readonly public Housekeeper $housekeeper;

    public function __construct(private array $events, array $whitelist_prototypes) {
        $this->whitelist = new Subscription($whitelist_prototypes, \nostriphant\Transpher\Relay\Condition::class);
        if (Subscription::disabled($whitelist_prototypes) === false) {
            $this->housekeeper = new Memory\Housekeeper($this);
        } else {
            $this->housekeeper = new NullHousekeeper();
        }
        call_user_func($this->housekeeper);
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
        if (call_user_func($this->whitelist, $value) === false) {
            return;
        } elseif (isset($offset)) {
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
