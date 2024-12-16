<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\NIP01\Message;

readonly class Event implements Type {

    public function __construct(
            private Event\Accepted $accepted,
            private \nostriphant\Transpher\Relay\Limits $limits
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        yield from ($this->limits)(new \nostriphant\NIP01\Event(...$payload[0]))(
                        accepted: $this->accepted,
                        rejected: fn(string $reason) => yield Message::ok($payload[0]['id'], false, 'invalid:' . $reason)
                );
    }
}
