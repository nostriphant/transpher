<?php

namespace nostriphant\Transpher\Relay\Incoming\Event\Accepted;

use nostriphant\NIP01\Event;

class Ephemeral {

    public function __construct(
            private \nostriphant\Transpher\Relay\Incoming\Context $context
    ) {

    }

    public function __invoke(Event $event) {
        yield from ($this->context->subscriptions)($event);
    }
}
