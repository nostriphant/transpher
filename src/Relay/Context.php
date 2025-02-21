<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\Transpher\Files;
use nostriphant\Stores\Store;
use nostriphant\NIP01\Message;

readonly class Context {

    public function __construct(
            public Store $events,
            public Files $files,
            private bool $authentication
    ) {
        
    }

    public function __invoke(Sender $client): Incoming {
        $client_context = new Incoming\Context($this->events, $this->files, new Subscriptions($client));

        $enabled_types = [
            new Incoming\Event(new Incoming\Event\Accepted($client_context), Incoming\Event\Limits::fromEnv()),
            new Incoming\Close($client_context),
            new Incoming\Req(new Incoming\Req\Accepted($client_context, Incoming\Req\Accepted\Limits::fromEnv()), Incoming\Req\Limits::fromEnv()),
            new Incoming\Count($client_context, Incoming\Count\Limits::fromEnv())
        ];

        if ($this->authentication) {
            $client(Message::auth(bin2hex(random_bytes(32))));
            $enabled_types[] = new Incoming\Auth(Incoming\Auth\Limits::fromEnv());
        }

        return new Incoming(...$enabled_types);
    }
}
