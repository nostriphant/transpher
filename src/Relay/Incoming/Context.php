<?php


namespace nostriphant\Transpher\Relay\Incoming;

readonly class Context {

    public function __construct(
            public \nostriphant\Stores\Store $events,
            public \nostriphant\Transpher\Files $files,
            public \nostriphant\Transpher\Relay\Subscriptions $subscriptions
    ) {

    }
}
