<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\Transpher\Stores\Store;
use nostriphant\Transpher\Relay\Subscriptions;
use nostriphant\NIP01\Message;

readonly class Incoming {

    public function __construct(private Store $events, private \nostriphant\Transpher\Files $files) {
        
    }

    public function __invoke(Subscriptions $subscriptions, Message $message): \Generator {
        yield from (match (strtoupper($message->type)) {
                    'EVENT' => new Incoming\Event(new Incoming\Event\Accepted($this->events, $this->files, $subscriptions), Incoming\Event\Limits::fromEnv()),
                    'CLOSE' => new Incoming\Close($subscriptions),
                    'REQ' => new Incoming\Req(new Incoming\Req\Accepted($this->events, $subscriptions, Incoming\Req\Accepted\Limits::fromEnv()), Incoming\Req\Limits::fromEnv()),
                    'COUNT' => new Incoming\Count($this->events, Incoming\Count\Limits::fromEnv()),
                    default => new Incoming\Unknown($message->type)
                })($message->payload);
    }
}
