<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Relay\Condition;
use function \Functional\map,
 \Functional\partial_left,
             \Functional\if_else;

readonly class Req implements Type {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions,
            private \nostriphant\Transpher\Relay\Sender $relay,
            
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        if (count($payload) < 2) {
            throw new \InvalidArgumentException('Invalid message');
        }

        $filter_prototypes = array_filter(array_slice($payload, 1));

        if (count($filter_prototypes) === 0) {
            yield Factory::closed($payload[0], 'Subscription filters are empty');
        } else {
            $filters = Condition::makeFiltersFromPrototypes(...$filter_prototypes);
            ($this->subscriptions)($payload[0], if_else($filters, fn() => $this->relay, fn() => false));
            yield from map(($this->events)($filters), partial_left([Factory::class, 'requestedEvent'], $payload[0]));
            yield Factory::eose($payload[0]);
        }
    }
}
