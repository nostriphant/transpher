<?php


namespace nostriphant\Transpher\Relay\Store;

use function \Functional\select;
use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use function Functional\some;
use nostriphant\NIP01\Event;

trait Memory {

    public function __construct(private array $events) {

    }

    public function __invoke(Subscription $subscription): \Generator {
        $to = new \nostriphant\Transpher\Relay\Conditions(\nostriphant\Transpher\Relay\Condition::class);
        $filters = array_map(fn(array $filter_prototype) => Filter::fromPrototype(...$to($filter_prototype)), $subscription->filter_prototypes);
        yield from select($this->events, fn(Event $event) => some(array_map(fn(Filter $filter) => $filter($event), $filters)));
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
