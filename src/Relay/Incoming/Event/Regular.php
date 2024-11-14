<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Event;

class Regular {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Files $files,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions
    ) {
        
    }

    public function __invoke(Event $event) {
        $this->events[$event->id] = $event;
        $kindClass = __NAMESPACE__ . '\\Kind' . $event->kind;
        if (class_exists($kindClass) === false) {
            yield from ($this->subscriptions)($event);
        } else {
            yield from $kindClass::validate($event)(
                    accepted: function (Event $event) use ($kindClass) {
                                (new $kindClass($this->events, $this->files))($event);
                                yield from ($this->subscriptions)($event);
                            },
                            rejected: fn(string $reason) => yield Factory::ok($event->id, false, 'invalid:' . $reason)
                    );
        }
    }
}
