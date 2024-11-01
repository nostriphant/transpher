<?php

namespace nostriphant\Transpher\Relay;

class Incoming {

    public function __construct(private Incoming\Context $context) {
        
    }

    public function __invoke(array $message): \Generator {
        switch (strtoupper($message[0])) {
            case 'EVENT':
                $type = new Incoming\Event($this->context->events, $this->context->subscriptions);
                break;

            case 'CLOSE':
                $type = new Incoming\Close($this->context->subscriptions);
                break;

            case 'REQ':
                $type = new Incoming\Req($this->context->events, $this->context->subscriptions, $this->context->relay);
                break;

            default:
                throw new \InvalidArgumentException('Message type ' . $message[0] . ' not supported');
        }

        yield from $type($message);
    }
}
