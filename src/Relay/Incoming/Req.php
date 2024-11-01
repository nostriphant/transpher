<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Relay\Condition;
use function \Functional\map,
 \Functional\partial_left;

readonly class Req implements Type {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        if (count($payload) < 2) {
            yield Factory::notice('Invalid message');
        } else {
            $filter_prototypes = array_filter(array_slice($payload, 1));

            if (count($filter_prototypes) === 0) {
                yield Factory::closed($payload[0], 'Subscription filters are empty');
            } else {
                $filters = Condition::makeFiltersFromPrototypes(...$filter_prototypes);
                ($this->subscriptions)($payload[0], $filters);
                yield from map(($this->events)($filters), partial_left([Factory::class, 'requestedEvent'], $payload[0]));
                yield Factory::eose($payload[0]);
            }
        }
    }
}
