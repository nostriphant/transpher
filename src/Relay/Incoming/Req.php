<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\NIP01\Message;

readonly class Req implements Type {

    public function __construct(
            private Req\Accepted $accepted,
            private \nostriphant\Transpher\Relay\Limits $limits
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        if (count($payload) < 2) {
            yield Message::notice('Invalid message');
        } else {
            yield from ($this->limits)(array_filter(array_filter(array_slice($payload, 1), 'is_array')))(
                            rejected: fn(string $reason) => yield Message::closed($payload[0], $reason),
                            accepted: fn(array $filter_prototypes) => yield from ($this->accepted)($payload[0], $filter_prototypes)
                    );
        }
    }
}
