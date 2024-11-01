<?php

namespace nostriphant\Transpher\Relay\Incoming;

readonly class Factory {

    public function __construct(private Context $context) {
        
    }

    public function __invoke(string $payload): \Generator {
        $message = \nostriphant\Transpher\Nostr::decode($payload);
        switch (strtoupper($message[0])) {
            case 'EVENT':
                $incoming = new Event($this->context->events, $this->context->subscriptions, $message);
                break;

            case 'CLOSE':
                $incoming = new Close($this->context->subscriptions, $message);
                break;

            case 'REQ':
                $incoming = new Req($this->context->events, $this->context->subscriptions, $this->context->relay, $message);
                break;

            default:
                throw new \InvalidArgumentException('Message type ' . $message[0] . ' not supported');
        }

        yield from $incoming();
    }
}
