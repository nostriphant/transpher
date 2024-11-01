<?php

namespace nostriphant\Transpher\Relay;

class Incoming {

    public function __construct(private Incoming\Context $context) {
        
    }

    public function __invoke(string $type, mixed ...$payload): \Generator {
        yield from (match (strtoupper($type)) {
                    'EVENT' => new Incoming\Event($this->context->events, $this->context->subscriptions),
                    'CLOSE' => new Incoming\Close($this->context->subscriptions),
                    'REQ' => new Incoming\Req($this->context->events, $this->context->subscriptions, $this->context->relay),
                    default => new Incoming\Unknown($type)
                })($payload);
    }
}
