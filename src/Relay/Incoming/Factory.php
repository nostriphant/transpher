<?php

namespace rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Store;
use rikmeijer\Transpher\Relay\Sender;

/**
 * Description of Factory
 *
 * @author rmeijer
 */
class Factory {


    static function fromMessage(array $message, Store $events, Sender $relay): \rikmeijer\Transpher\Relay\Incoming {
        switch (strtoupper($message[0])) {
            case 'EVENT':
                return Event::fromMessage($message)($events);

            case 'CLOSE':
                return Close::fromMessage($message)();

            case 'REQ':
                return Req::fromMessage($message)($events, $relay);

            default:
                throw new \InvalidArgumentException('Message type ' . $message[0] . ' not supported');
        }
    }
}
