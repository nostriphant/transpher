<?php

namespace nostriphant\Transpher\Relay\Context;

use nostriphant\Transpher\Files;
use nostriphant\Stores\Store;
use nostriphant\Transpher\Relay\Incoming;
use nostriphant\Transpher\Relay\Subscriptions;
use nostriphant\Transpher\Relay\Sender;

readonly class Unrestricted implements \nostriphant\Transpher\Relay\Context {

    public function __construct(
            public Store $events,
            public Files $files
    ) {
        
    }

    #[\Override]
    public function __invoke(Sender $client): Incoming {
        $client_context = new Incoming\Context($this->events, $this->files, new Subscriptions($client));

        return new Incoming(
                new \nostriphant\Transpher\Relay\Incoming\Event(new Incoming\Event\Accepted($client_context), Incoming\Event\Limits::fromEnv()),
                new \nostriphant\Transpher\Relay\Incoming\Close($client_context),
                new \nostriphant\Transpher\Relay\Incoming\Req(new Incoming\Req\Accepted($client_context, Incoming\Req\Accepted\Limits::fromEnv()), Incoming\Req\Limits::fromEnv()),
                new \nostriphant\Transpher\Relay\Incoming\Count($client_context, Incoming\Count\Limits::fromEnv())
        );
    }
}
