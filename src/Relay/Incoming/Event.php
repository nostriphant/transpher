<?php

namespace nostriphant\Transpher\Relay\Incoming;

use function \Functional\first;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Event\KindClass;
use nostriphant\Transpher\Relay\Condition;

readonly class Event implements \nostriphant\Transpher\Relay\Incoming {

    public function __construct(private \nostriphant\Transpher\Nostr\Event $event) {
        
    }

    #[\Override]
    static function fromMessage(array $message): self {
        return new self(new \nostriphant\Transpher\Nostr\Event(...$message[1]));
    }

    #[\Override]
    public function __invoke(Context $context): \Generator {
        if (\nostriphant\Transpher\Nostr\Event::verify($this->event) === false) {
            yield Factory::ok($this->event->id, false, 'invalid:signature is wrong');
        } else {
            $replaceable_events = [];
            switch (\nostriphant\Transpher\Nostr\Event::determineClass($this->event)) {
                case KindClass::REGULAR:
                    $context->events[$this->event->id] = $this->event;
                    $kindClass = __CLASS__ . '\\Kind' . $this->event->kind;
                    if (class_exists($kindClass)) {
                        $incoming_kind = new $kindClass($this->event);
                        $incoming_kind($context->events);
                    }
                    break;

                case KindClass::REPLACEABLE:
                    $replaceable_events = ($context->events)(Condition::makeFiltersFromPrototypes([
                                'kinds' => [$this->event->kind],
                                'authors' => [$this->event->pubkey]
                    ]));

                    $context->events[$this->event->id] = $this->event;
                    foreach ($replaceable_events as $replaceable_event) {
                        $replace_id = $replaceable_event->id;
                        if ($replaceable_event->created_at === $this->event->created_at) {
                            $replace_id = max($replaceable_event->id, $this->event->id);
                        }
                        unset($context->events[$replace_id]);
                    }
                    break;

                case KindClass::EPHEMERAL:
                    break;

                case KindClass::ADDRESSABLE:
                    $replaceable_events = ($context->events)(Condition::makeFiltersFromPrototypes([
                                'kinds' => [$this->event->kind],
                                'authors' => [$this->event->pubkey],
                                '#d' => \nostriphant\Transpher\Nostr\Event::extractTagValues($this->event, 'd')
                    ]));

                    $context->events[$this->event->id] = $this->event;
                    foreach ($replaceable_events as $replaceable_event) {
                        $replace_id = $replaceable_event->id;
                        if ($replaceable_event->created_at === $this->event->created_at) {
                            $replace_id = max($replaceable_event->id, $this->event->id);
                        }
                        unset($context->events[$replace_id]);
                    }
                    break;

                case KindClass::UNDEFINED:
                default:
                    yield Factory::notice('Undefined event kind ' . $this->event->kind);
                    return;
            }

            if (empty($context->subscriptions) === false) {
                ($context->subscriptions)(function (callable $subscription, string $subscriptionId) {
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
