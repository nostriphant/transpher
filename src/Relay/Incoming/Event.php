<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Event\KindClass;
use nostriphant\Transpher\Relay\Condition;

readonly class Event {

    
    private \nostriphant\Transpher\Nostr\Event $event;

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions,
            array $message
    ) {
        $this->event = new \nostriphant\Transpher\Nostr\Event(...$message[1]);
    }

    public function __invoke(): \Generator {
        if (\nostriphant\Transpher\Nostr\Event::verify($this->event) === false) {
            yield Factory::ok($this->event->id, false, 'invalid:signature is wrong');
        } else {
            $replaceable_events = [];
            switch (\nostriphant\Transpher\Nostr\Event::determineClass($this->event)) {
                case KindClass::REGULAR:
                    $this->events[$this->event->id] = $this->event;
                    $kindClass = __CLASS__ . '\\Kind' . $this->event->kind;
                    if (class_exists($kindClass)) {
                        $incoming_kind = new $kindClass($this->event);
                        $incoming_kind($this->events);
                    }
                    break;

                case KindClass::REPLACEABLE:
                    $replaceable_events = ($this->events)(Condition::makeFiltersFromPrototypes([
                                'kinds' => [$this->event->kind],
                                'authors' => [$this->event->pubkey]
                    ]));

                    $this->events[$this->event->id] = $this->event;
                    foreach ($replaceable_events as $replaceable_event) {
                        $replace_id = $replaceable_event->id;
                        if ($replaceable_event->created_at === $this->event->created_at) {
                            $replace_id = max($replaceable_event->id, $this->event->id);
                        }
                        unset($this->events[$replace_id]);
                    }
                    break;

                case KindClass::EPHEMERAL:
                    break;

                case KindClass::ADDRESSABLE:
                    $replaceable_events = ($this->events)(Condition::makeFiltersFromPrototypes([
                                'kinds' => [$this->event->kind],
                                'authors' => [$this->event->pubkey],
                                '#d' => \nostriphant\Transpher\Nostr\Event::extractTagValues($this->event, 'd')
                    ]));

                    $this->events[$this->event->id] = $this->event;
                    foreach ($replaceable_events as $replaceable_event) {
                        $replace_id = $replaceable_event->id;
                        if ($replaceable_event->created_at === $this->event->created_at) {
                            $replace_id = max($replaceable_event->id, $this->event->id);
                        }
                        unset($this->events[$replace_id]);
                    }
                    break;

                case KindClass::UNDEFINED:
                default:
                    yield Factory::notice('Undefined event kind ' . $this->event->kind);
                    return;
            }

            if (empty($this->subscriptions) === false) {
                ($this->subscriptions)(function (callable $subscription, string $subscriptionId) {
                    $to = $subscription($this->event);
                    if ($to === false) {
                        return false;
                    }
                    $to(Factory::requestedEvent($subscriptionId, $this->event));
                    $to(Factory::eose($subscriptionId));
                    return true;
                });
            }
            yield Factory::accept($this->event->id);
        }
    }
}
