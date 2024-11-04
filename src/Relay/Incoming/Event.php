<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Event\KindClass;
use nostriphant\Transpher\Relay\Condition;

readonly class Event implements Type {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions,
            private Event\Constraints $constraints = new Event\Constraints()
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        $event = new \nostriphant\Transpher\Nostr\Event(...$payload[0]);
        $constraint = ($this->constraints)($event);
        switch ($constraint->result) {
            case Constraint\Result::REJECTED:
                yield Factory::ok($event->id, false, 'invalid:' . $constraint->reason);
                break;

            case Constraint\Result::ACCEPTED:
                $replaceable_events = [];
                switch (\nostriphant\Transpher\Nostr\Event::determineClass($event)) {
                    case KindClass::REGULAR:
                        $this->events[$event->id] = $event;
                        $kindClass = __CLASS__ . '\\Kind' . $event->kind;
                        if (class_exists($kindClass)) {
                            $incoming_kind = new $kindClass($event);
                            $incoming_kind($this->events);
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
                break;
        }
    }
}
