<?php


namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use function Functional\some;
use nostriphant\NIP01\Event;

class Memory implements \nostriphant\Transpher\Relay\Store {

    private array $events;
    private Subscription $whitelist;

    public function __construct(array $events, array $whitelist_prototypes) {
        $this->whitelist = new Subscription($whitelist_prototypes);
        $this->events = array_filter($events, fn(Event $event) => ($this->whitelist)($event) !== false);
    }

    #[\Override]
    public function __invoke(array ...$filter_prototypes): Results {
        $to = new \nostriphant\Transpher\Relay\Conditions(\nostriphant\Transpher\Relay\Condition::class);
        $filters = array_map(fn(array $filter_prototype) => Filter::fromPrototype(...$to($filter_prototype)), $filter_prototypes);

        return new Results(function (callable $callback) use ($filters) {
                    array_reduce(array_filter($this->events, fn(Event $event) => some(array_map(fn(Filter $filter) => $filter($event), $filters))), fn($carry, Event $event) => $callback($event), 0);
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
