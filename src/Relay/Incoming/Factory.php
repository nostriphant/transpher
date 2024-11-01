<?php

namespace nostriphant\Transpher\Relay\Incoming;

readonly class Factory {

    public function __construct(private Context $context) {
        
    }

    public function __invoke(string $payload): \Generator {
        $message = \nostriphant\Transpher\Nostr::decode($payload);
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

        yield from $incoming($this->context);
    }
}
