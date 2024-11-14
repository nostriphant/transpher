<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Event;

class Accepted {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Files $files,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions
    ) {
        
    }

    public function __invoke(Event $event): \Generator {
        yield from Event::alternateClass($event)(
                        regular: new Regular($this->events, $this->files, $this->subscriptions),
                        replaceable: new Replaceable($this->events, $this->subscriptions),
                        ephemeral: new Ephemeral($this->subscriptions),
                        addressable: new Addressable($this->events, $this->subscriptions),
                        undefined: new Undefined()
                );
    }
}
