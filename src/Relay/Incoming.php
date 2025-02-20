<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Message;

readonly class Incoming {

    public function __invoke(\nostriphant\Transpher\Relay\Incoming\Context $context, Message $message): \Traversable {
        yield from (match (strtoupper($message->type)) {
                    'AUTH' => new Incoming\Auth(Incoming\Auth\Limits::fromEnv()),
                    'EVENT' => new Incoming\Event(new Incoming\Event\Accepted($context), Incoming\Event\Limits::fromEnv()),
                    'CLOSE' => new Incoming\Close($context),
                    'REQ' => new Incoming\Req(new Incoming\Req\Accepted($context, Incoming\Req\Accepted\Limits::fromEnv()), Incoming\Req\Limits::fromEnv()),
                    'COUNT' => new Incoming\Count($context, Incoming\Count\Limits::fromEnv()),
                    default => new Incoming\Unknown($message->type)
                })($message->payload);
    }
}
