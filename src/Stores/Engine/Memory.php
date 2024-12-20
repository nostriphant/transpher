<?php


namespace nostriphant\Transpher\Stores\Engine;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Stores\Conditions;
use nostriphant\Transpher\Stores\Results;
use nostriphant\Transpher\Stores\Engine;
use nostriphant\Transpher\Stores\Housekeeper;

final class Memory implements Engine {

    public function __construct(private array $events) {
        
    }

    #[\Override]
    static function housekeeper(Engine $engine): Housekeeper {
        return new Memory\Housekeeper($engine);
    }

    #[\Override]
    public function __invoke(array ...$filter_prototypes): Results {
        $limit = array_reduce($filter_prototypes, fn(?int $limit, array $filter_prototype) => $filter_prototype['limit'] ?? $limit, null);
        $subscription = Memory\Condition::makeConditions(new Conditions($filter_prototypes));
        return new Results(function () use ($subscription, $limit) {
                    $events = array_filter($this->events, $subscription);
                    if (isset($limit)) {
                        usort($events, function (Event $event1, Event $event2): int {
                            if ($event1->created_at !== $event2->created_at) {
                                return $event2->created_at - $event1->created_at;
                            }
                            return strcasecmp($event1->id, $event2->id);
                        });
                        $events = array_slice($events, 0, $limit);
                    }
                    yield from $events;
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
