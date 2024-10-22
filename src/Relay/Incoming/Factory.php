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
        return function (array $message) use ($relay): \Generator {
            switch (strtoupper($message[0])) {
                case 'EVENT':
                    $incoming = Event::fromMessage($message)($this->events);
                    break;

                case 'CLOSE':
                    $incoming = Close::fromMessage($message)();
                    break;

                case 'REQ':
                    $incoming = Req::fromMessage($message)($this->events, $relay);
                    break;

                default:
                    throw new \InvalidArgumentException('Message type ' . $message[0] . ' not supported');
            }

            yield from $incoming();
        };
    }
}
