<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Event\KindClass;
use nostriphant\Transpher\Relay\Condition;

readonly class Event implements Type {

    public function __construct(
            private Event\Accepted $accepted,
            private \nostriphant\Transpher\Relay\Limits $limits
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        yield from ($this->limits)(new \nostriphant\Transpher\Nostr\Event(...$payload[0]))(
                        accepted: $this->accepted,
                        rejected: fn(string $reason) => yield Factory::ok($payload[0]['id'], false, 'invalid:' . $reason)
                );
    }
}
