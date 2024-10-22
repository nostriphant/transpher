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

    static function make(Context $context): callable {
        return function (array $message) use ($context): \Generator {
            switch (strtoupper($message[0])) {
                case 'EVENT':
                    $incoming = Event::fromMessage($message);
                    break;

                case 'CLOSE':
                    $incoming = Close::fromMessage($message);
                    break;

                case 'REQ':
                    $incoming = Req::fromMessage($message);
                    break;

                default:
                    throw new \InvalidArgumentException('Message type ' . $message[0] . ' not supported');
            }

            yield from $incoming($context);
        };
    }
}
