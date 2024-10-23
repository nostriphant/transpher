<?php

namespace rikmeijer\Transpher\Relay\Incoming;

readonly class Factory {

    static function make(array $message): \rikmeijer\Transpher\Relay\Incoming {
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

        return $incoming;
    }
}
