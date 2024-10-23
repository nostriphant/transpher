<?php

namespace rikmeijer\Transpher;

use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\Relay\Incoming\Context;

class Relay {

    public function __construct(private Context $context) {
        
    }
    
    public function __invoke(Context $context): callable {
        $factory = Relay\Incoming\Factory::make(Context::merge($context, $this->context));
        return function (string $payload) use ($factory): \Generator {
            try {
                yield from $factory(\rikmeijer\Transpher\Nostr::decode($payload));
            } catch (\InvalidArgumentException $ex) {
                yield Factory::notice($ex->getMessage());
            }
        };
    }
}
