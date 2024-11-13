<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Event\KindClass;
use nostriphant\Transpher\Relay\Condition;
use nostriphant\Transpher\Nostr\Event;

class Accepted {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private string $files,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions
    ) {
        
    }

    public function __invoke(Event $event): \Generator {
        switch (Event::determineClass($event)) {
            case KindClass::REGULAR:
                yield from (new Regular($this->events, $this->files, $this->subscriptions))($event);
                break;

            case KindClass::REPLACEABLE:
                yield from (new Replaceable($this->events, $this->subscriptions))($event);
                break;

            case KindClass::EPHEMERAL:
                yield from (new Ephemeral($this->subscriptions))($event);
                break;

            case KindClass::ADDRESSABLE:
                yield from (new Addressable($this->events, $this->subscriptions))($event);
                break;

            case KindClass::UNDEFINED:
            default:
                yield from (new Undefined())($event);
                break;
        }

    }
}
