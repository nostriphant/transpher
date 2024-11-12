<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Event\KindClass;
use nostriphant\Transpher\Relay\Condition;
use nostriphant\Transpher\Relay\Incoming\Constraint\Result;

class Accepted {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private string $files,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions,
    ) {
        
    }

    public function __invoke(\nostriphant\Transpher\Nostr\Event $event): \Generator {
        $replaceable_events = [];
        switch (\nostriphant\Transpher\Nostr\Event::determineClass($event)) {
            case KindClass::REGULAR:
                $this->events[$event->id] = $event;
                $kindClass = __NAMESPACE__ . '\\Kind' . $event->kind;
                if (class_exists($kindClass)) {
                    $incoming_kind = new $kindClass($this->events, $this->files);
                    $validation = $kindClass::validate($event);
                    switch ($validation->result) {
                        case Result::ACCEPTED:
                            $incoming_kind($event);
                            break;

                        case Result::REJECTED:
                            yield Factory::ok($event->id, false, 'invalid:' . $validation->reason);
                            return;
                    }
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
                break;

            case KindClass::EPHEMERAL:
                break;

            case KindClass::ADDRESSABLE:
                $replaceable_events = ($this->events)(Condition::makeFiltersFromPrototypes([
                            'kinds' => [$event->kind],
                            'authors' => [$event->pubkey],
                            '#d' => \nostriphant\Transpher\Nostr\Event::extractTagValues($event, 'd')
                ]));

                $this->events[$event->id] = $event;
                foreach ($replaceable_events as $replaceable_event) {
                    $replace_id = $replaceable_event->id;
                    if ($replaceable_event->created_at === $event->created_at) {
                        $replace_id = max($replaceable_event->id, $event->id);
                    }
                    unset($this->events[$replace_id]);
                }
                break;

            case KindClass::UNDEFINED:
            default:
                yield Factory::notice('Undefined event kind ' . $event->kind);
                return;
        }

        if (empty($this->subscriptions) === false) {
            ($this->subscriptions)(function (callable $subscription, string $subscriptionId) use ($event) {
                $to = $subscription($event);
                if ($to === false) {
                    return false;
                }
                $to(Factory::requestedEvent($subscriptionId, $event));
                $to(Factory::eose($subscriptionId));
                return true;
            });
        }
        yield Factory::accept($event->id);
    }
}
