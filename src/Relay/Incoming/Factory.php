<?php

namespace nostriphant\Transpher\Relay\Incoming;

readonly class Factory {

    static function make(array $message): \nostriphant\Transpher\Relay\Incoming {
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
