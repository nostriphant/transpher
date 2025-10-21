<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Relay\Files;

class Accepted {

    public function __construct(
            private \nostriphant\Stores\Store $events,
            private Files $files,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions
    ) {
        
    }

    public function __invoke(Event $event): \Generator {
        yield from Event::alternateClass($event)(
                        regular: new Accepted\Regular($this->events, $this->files, $this->subscriptions),
                        replaceable: new Accepted\Replaceable($this->events, $this->subscriptions),
                        ephemeral: new Accepted\Ephemeral($this->subscriptions),
                        addressable: new Accepted\Addressable($this->events, $this->subscriptions),
                        undefined: new Accepted\Undefined()
                );
    }
}
