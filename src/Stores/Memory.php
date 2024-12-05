<?php


namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use function Functional\some;
use nostriphant\NIP01\Event;

class Memory implements \nostriphant\Transpher\Relay\Store {

    private array $events;

    public function __construct(array $events, private Subscription $whitelist) {
        $this->events = array_filter($events, fn(Event $event) => $whitelist($event) !== false);
    }

    #[\Override]
    public function __invoke(Subscription $subscription): Results {
        $to = new \nostriphant\Transpher\Relay\Conditions(\nostriphant\Transpher\Relay\Condition::class);
        $filters = array_map(fn(array $filter_prototype) => Filter::fromPrototype(...$to($filter_prototype)), $subscription->filter_prototypes);

        return new Results(results: call_user_func(function (array $filters) {
            foreach ($this->events as $event) {
                        if (some(array_map(fn(Filter $filter) => $filter($event), $filters))) {
                            yield $event;
                        }
                    }
                }, $filters));
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
