<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Relay\Condition;
use function \Functional\map,
 \Functional\partial_left;

readonly class Count implements Type {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Relay\Limits $limits
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        if (count($payload) < 2) {
            yield Factory::notice('Invalid message');
        } else {
            yield from ($this->limits)(array_filter(array_slice($payload, 1)))(
                            accepted: fn(array $filter_prototypes) => yield Factory::countResponse($payload[0], count(($this->events)(Condition::makeFiltersFromPrototypes(...$filter_prototypes)))),
                    rejected: fn(string $reason) => yield Factory::closed($payload[0], $reason)
            );
        }
    }
}
