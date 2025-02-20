<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\NIP01\Event;

readonly class Accepted {

    public function __construct(
            private \nostriphant\Transpher\Relay\Incoming\Context $context
    ) {
        
    }

    public function __invoke(Event $event): \Generator {
        yield from Event::alternateClass($event)(
                        regular: new Accepted\Regular($this->context),
                        replaceable: new Accepted\Replaceable($this->context),
                        ephemeral: new Accepted\Ephemeral($this->context),
                        addressable: new Accepted\Addressable($this->context),
                        undefined: new Accepted\Undefined($this->context)
                );
    }
}
