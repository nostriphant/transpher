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
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions,
    ) {
        
    }

    public function __invoke(Event $event): \Generator {
        $replaceable_events = [];
        switch (Event::determineClass($event)) {
            case KindClass::REGULAR:
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
                break;

            case KindClass::REPLACEABLE:
                $replaceable_events = ($this->events)(Condition::makeFiltersFromPrototypes([
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
                break;

            case KindClass::EPHEMERAL:
                yield from ($this->subscriptions)($event);
                break;

            case KindClass::ADDRESSABLE:
                $replaceable_events = ($this->events)(Condition::makeFiltersFromPrototypes([
                            'kinds' => [$event->kind],
                            'authors' => [$event->pubkey],
                            '#d' => Event::extractTagValues($event, 'd')
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
                break;

            case KindClass::UNDEFINED:
            default:
                yield Factory::notice('Undefined event kind ' . $event->kind);
                break;
        }

    }
}
