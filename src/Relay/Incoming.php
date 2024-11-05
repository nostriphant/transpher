<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\Transpher\Relay\Store;
use nostriphant\Transpher\Relay\Subscriptions;
use nostriphant\Transpher\Nostr\Message;

readonly class Incoming {

    public function __construct(private Store $events) {
        
    }

    public function __invoke(Subscriptions $subscriptions, Message $message): \Generator {
        yield from (match (strtoupper($message->type)) {
                    'EVENT' => new Incoming\Event($this->events, $subscriptions, Limits::fromEnv(Incoming\Event\Limits::class)),
                    'CLOSE' => new Incoming\Close($subscriptions),
                    'REQ' => new Incoming\Req($this->events, $subscriptions, Limits::fromEnv(Incoming\Req\Limits::class)),
                    default => new Incoming\Unknown($message->type)
                })($message->payload);
    }
}
