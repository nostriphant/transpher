<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\NIP01\Message;

readonly class Count implements Type {

    public function __construct(
            private \nostriphant\Transpher\Relay\Incoming\Context $context,
            private \nostriphant\Transpher\Relay\Limits $limits
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        if (count($payload) < 2) {
            yield Message::notice('Invalid message');
        } else {
            yield from ($this->limits)(array_filter(array_slice($payload, 1)))(
                            accepted: fn(array $filter_prototypes) => yield Message::count($payload[0], ['count' => iterator_count($this->context->events(...$filter_prototypes))]),
                            rejected: fn(string $reason) => yield Message::closed($payload[0], $reason)
                    );
        }
    }
}
