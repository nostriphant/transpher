<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\NIP01\Event;

class Ephemeral {

    public function __construct(
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions
    ) {

    }

    public function __invoke(Event $event) {
        yield from ($this->subscriptions)($event);
    }
}
