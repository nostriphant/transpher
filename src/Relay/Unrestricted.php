<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\Transpher\Files;
use nostriphant\Stores\Store;

readonly class Unrestricted implements Context {

    public function __construct(
            public Store $events,
            public Files $files
    ) {
        
    }

    #[\Override]
    public function __invoke(Sender $client): Incoming {
        $client_context = new Incoming\Context($this->events, $this->files, new Subscriptions($client));

        return new Incoming(
                new Incoming\Event(new Incoming\Event\Accepted($client_context), Incoming\Event\Limits::fromEnv()),
                new Incoming\Close($client_context),
                new Incoming\Req(new Incoming\Req\Accepted($client_context, Incoming\Req\Accepted\Limits::fromEnv()), Incoming\Req\Limits::fromEnv()),
                new Incoming\Count($client_context, Incoming\Count\Limits::fromEnv())
        );
    }
}
