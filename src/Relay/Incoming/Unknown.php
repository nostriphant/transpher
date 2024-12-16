<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\NIP01\Message;

class Unknown implements Type {

    public function __construct(private string $type) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        yield Message::notice('Message type ' . $this->type . ' not supported');
    }
}
