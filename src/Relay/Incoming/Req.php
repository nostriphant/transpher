<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Relay\Condition;
use nostriphant\Transpher\Nostr\Subscription;

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
            yield from ($this->limits)(array_filter(array_slice($payload, 1)))(
                            rejected: fn(string $reason) => yield Factory::closed($payload[0], $reason),
                            accepted: fn(array $filter_prototypes) => yield from ($this->accepted)($payload[0], Subscription::make(Condition::map(), ...$filter_prototypes))
                    );
        }
    }
}
