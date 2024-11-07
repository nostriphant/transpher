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
            $filter_prototypes = array_filter(array_slice($payload, 1));
            $constraint = ($this->limits)($filter_prototypes);
            switch ($constraint->result) {
                case Constraint\Result::REJECTED:
                    yield Factory::closed($payload[0], $constraint->reason);
                    break;

                case Constraint\Result::ACCEPTED:
                    $filters = Condition::makeFiltersFromPrototypes(...$filter_prototypes);
                    yield Factory::countResponse($payload[0], count(($this->events)($filters)));
                    break;
            }
        }
    }
}
