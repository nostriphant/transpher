<?php


namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;

class Memory implements \nostriphant\Transpher\Relay\Store {

    private array $events;
    private Subscription $whitelist;

    public function __construct(array $events, array $whitelist_prototypes) {
        $this->whitelist = Subscription::make($whitelist_prototypes, \nostriphant\Transpher\Relay\Condition::class);
        $this->events = array_filter($events, fn(Event $event) => ($this->whitelist)($event) !== false);
    }

    #[\Override]
    public function __invoke(array ...$filter_prototypes): Results {
        $subscription = Subscription::make($filter_prototypes, \nostriphant\Transpher\Relay\Condition::class);
        return new Results(function (callable $callback) use ($subscription) {
                    array_reduce(array_filter($this->events, $subscription), fn($carry, Event $event) => $callback($event), 0);
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
}
