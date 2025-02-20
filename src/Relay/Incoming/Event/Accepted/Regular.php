<?php

namespace nostriphant\Transpher\Relay\Incoming\Event\Accepted;

use nostriphant\NIP01\Message;
use nostriphant\NIP01\Event;

class Regular {

    public function __construct(
            private \nostriphant\Transpher\Relay\Incoming\Context $context
    ) {

    }

    public function __invoke(Event $event) {
        $this->context->events[$event->id] = $event;
        $kindClass = Regular\Kind::class . $event->kind;
        if (class_exists($kindClass) === false) {
            yield from $this->context->subscriptions($event);
        } else {
            yield from $kindClass::validate($event)(
                    accepted: function (Event $event) use ($kindClass) {
                                (new $kindClass($this->context->events, $this->context->files))($event);
                                yield from $this->context->subscriptions($event);
                            },
                            rejected: fn(string $reason) => yield Message::ok($event->id, false, 'invalid:' . $reason)
                    );
        }
    }
}
