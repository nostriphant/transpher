<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\Transpher\Relay\Store;
use nostriphant\Transpher\Relay\Subscriptions;
use nostriphant\Transpher\Nostr\Message;

readonly class Incoming {

    public function __construct(private Store $events, private string $files) {
        
    }

    public function __invoke(Subscriptions $subscriptions, Message $message): \Generator {
        yield from (match (strtoupper($message->type)) {
                    'EVENT' => new Incoming\Event(new Incoming\Event\Accepted($this->events, $this->files, $subscriptions), Limits::fromEnv(Incoming\Event\Limits::class)),
                    'CLOSE' => new Incoming\Close($subscriptions),
                    'REQ' => new Incoming\Req($this->events, $subscriptions, Limits::fromEnv(Incoming\Req\Limits::class)),
                    'COUNT' => new Incoming\Count($this->events, Limits::fromEnv(Incoming\Count\Limits::class)),
                    default => new Incoming\Unknown($message->type)
                })($message->payload);
    }
}
