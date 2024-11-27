<?php


namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;

class Replaceable {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions
    ) {

    }

    public function __invoke(Event $event) {
        $replaceable_events = ($this->events)(Subscription::make([
                    'kinds' => [$event->kind],
                    'authors' => [$event->pubkey]
        ]));

        $this->events[$event->id] = $event;
        foreach ($replaceable_events as $replaceable_event) {
            $replace_id = $replaceable_event->id;
            if ($replaceable_event->created_at === $event->created_at) {
                $replace_id = max($replaceable_event->id, $event->id);
            }
            unset($this->events[$replace_id]);
        }
        yield from ($this->subscriptions)($event);
    }
}
