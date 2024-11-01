<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;

class Unknown implements Type {

    public function __construct(private string $type) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        yield Factory::notice('Message type ' . $this->type . ' not supported');
    }
}
