<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\NIP01\Message;

readonly class Close implements Type {

    public function __construct(private \nostriphant\Transpher\Relay\Subscriptions $subscriptions) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        if (count($payload) < 1) {
            yield Message::notice('Missing subscription ID');
        } else {
            ($this->subscriptions)($payload[0]);
            yield Message::closed($payload[0]);
        }
    }
}
