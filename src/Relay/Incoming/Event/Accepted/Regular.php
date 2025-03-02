<?php

namespace nostriphant\Transpher\Relay\Incoming\Event\Accepted;

use nostriphant\NIP01\Message;
use nostriphant\NIP01\Event;

class Regular {

    public function __construct(
            private \nostriphant\Stores\Store $events,
            private \nostriphant\Transpher\Files $files,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions
    ) {
        
    }

    public function __invoke(Event $event) {
        $this->events[$event->id] = $event;
        $kindClass = Regular\Kind::class . $event->kind;
        if (class_exists($kindClass) === false) {
            yield from ($this->subscriptions)($event);
        } else {
            yield from $kindClass::validate($event)(
                    accepted: function (Event $event) use ($kindClass) {
                                (new $kindClass($this->events, $this->files))($event);
                                yield from ($this->subscriptions)($event);
                            },
                            rejected: fn(string $reason) => yield Message::ok($event->id, false, 'invalid:' . $reason)
                    );
        }
    }
}
