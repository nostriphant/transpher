<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Relay\Condition;

readonly class Req implements Type {

    public function __construct(
            private Req\Accepted $accepted,
            private \nostriphant\Transpher\Relay\Limits $limits
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        if (count($payload) < 2) {
            yield Factory::notice('Invalid message');
        } else {
            $filter_prototypes = array_filter(array_slice($payload, 1));
            $constraint = ($this->limits)($filter_prototypes);
            yield from $constraint(
                    rejected: fn(string $reason) => yield Factory::closed($payload[0], $reason),
                            accepted: fn() => yield from ($this->accepted)($payload[0], Condition::makeFiltersFromPrototypes(...$filter_prototypes))
                    );
        }
    }
}
