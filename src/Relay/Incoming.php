<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\Transpher\Relay\Store;
use nostriphant\Transpher\Relay\Subscriptions;
use nostriphant\Transpher\Nostr\Message;

readonly class Incoming {

    public function __construct(private Store $events, private Subscriptions $subscriptions) {
        
    }

    public function __invoke(Message $message): \Generator {
        yield from (match (strtoupper($message->type)) {
                    'EVENT' => new Incoming\Event($this->events, $this->subscriptions),
                    'CLOSE' => new Incoming\Close($this->subscriptions),
                    'REQ' => new Incoming\Req($this->events, $this->subscriptions),
                    default => new Incoming\Unknown($message->type)
                })($message->payload);
    }
}
