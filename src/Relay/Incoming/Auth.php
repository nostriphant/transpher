<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\NIP01\Message;
use nostriphant\NIP01\Event;

readonly class Auth implements Type {

    public function __construct(
            private \nostriphant\Transpher\Relay\Limits $limits
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        if (count($payload) < 1) {
            yield Message::notice('Invalid message');
        } else {
            yield from ($this->limits)(new Event(...$payload[0]))(
                            accepted: fn(Event $event) => yield Message::ok($event->id, true),
                            rejected: fn(string $reason) => yield Message::closed($payload[0], $reason)
                    );
        }
    }
}
