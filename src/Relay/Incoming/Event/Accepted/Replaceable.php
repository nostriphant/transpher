<?php

namespace nostriphant\Transpher\Relay\Incoming\Event\Accepted;

use nostriphant\NIP01\Event;

class Replaceable {

    public function __construct(
            private \nostriphant\Transpher\Relay\Incoming\Context $context
    ) {

    }

    public function __invoke(Event $event) {
        $this->context->events[$event->id] = $event;
        $replaceable_events = $this->context->events([
            'kinds' => [$event->kind],
            'authors' => [$event->pubkey]
        ]);
        foreach ($replaceable_events as $replaceable_event) {
            if ($replaceable_event === $event) {
                continue;
            }

            $replace_id = $replaceable_event->id;
            if ($replaceable_event->created_at === $event->created_at) {
                $replace_id = max($replaceable_event->id, $event->id);
            }
            unset($this->context->events[$replace_id]);
        }
        yield from $this->context->subscriptions($event);
    }
}
