<?php

namespace rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Store;
use rikmeijer\Transpher\Relay\Sender;

/**
 * Description of Factory
 *
 * @author rmeijer
 */
readonly class Factory {

    public function __construct(private Store $events) {
        
    }

    public function __invoke(Sender $relay): callable {
        return function (array $message) use ($relay): \rikmeijer\Transpher\Relay\Incoming {
            switch (strtoupper($message[0])) {
                case 'EVENT':
                    return Event::fromMessage($message)($this->events);

                case 'CLOSE':
                    return Close::fromMessage($message)();

                case 'REQ':
                    return Req::fromMessage($message)($this->events, $relay);

                default:
                    throw new \InvalidArgumentException('Message type ' . $message[0] . ' not supported');
            }
        };
    }
}
