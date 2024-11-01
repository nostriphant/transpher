<?php

namespace nostriphant\Transpher\Relay;

class Incoming {

    public function __construct(private Incoming\Context $context) {
        
    }

    public function __invoke(array $message): \Generator {
        switch (strtoupper($message[0])) {
            case 'EVENT':
                $type = new Incoming\Event($this->context->events, $this->context->subscriptions, $message);
                break;

            case 'CLOSE':
                $type = new Incoming\Close($this->context->subscriptions, $message);
                break;

            case 'REQ':
                $type = new Incoming\Req($this->context->events, $this->context->subscriptions, $this->context->relay, $message);
                break;

            default:
                throw new \InvalidArgumentException('Message type ' . $message[0] . ' not supported');
        }

        yield from $type();
    }
}
