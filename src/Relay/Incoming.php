<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\Transpher\Nostr\Message;

class Incoming {

    public function __construct(private Incoming\Context $context) {
        
    }

    public function __invoke(Message $message): \Generator {
        yield from (match (strtoupper($message->type)) {
                    'EVENT' => new Incoming\Event($this->context->events, $this->context->subscriptions),
                    'CLOSE' => new Incoming\Close($this->context->subscriptions),
                    'REQ' => new Incoming\Req($this->context->events, $this->context->subscriptions),
                    default => new Incoming\Unknown($message->type)
                })($message->payload);
    }
}
