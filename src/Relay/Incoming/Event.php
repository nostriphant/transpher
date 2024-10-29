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
                    switch ($this->event->kind) {
                        case 5:
                            $prototypes = [];
                            $possible_references = [
                                'e' => 'ids',
                                'k' => 'kinds',
                                'a' => fn(array $tags): array => array_map(fn(array $address) => [
                                    'kinds' => [$address[0]],
                                    'authors' => [$address[1]],
                                    'until' => $this->event->created_at,
                                    '#d' => [$address[2]]
                                        ], array_map(fn(array $tag) => explode(':', $tag[1]), $tags))
                            ];
                            foreach ($possible_references as $possible_reference => $possible_filter_field) {
                                $tags = array_filter($this->event->tags, fn(array $tag) => $tag[0] === $possible_reference);
                                if (empty($tags)) {
                                    continue;
                                }

                                if (is_string($possible_filter_field)) {
                                    $prototypes[] = [
                                        'authors' => [$this->event->pubkey],
                                        $possible_filter_field => array_map(fn(array $tag) => $tag[1], $tags)
                                    ];
                                } elseif (is_callable($possible_filter_field)) {
                                    $prototypes = array_merge($prototypes, $possible_filter_field($tags));
                                }
                            }

                            if (empty($prototypes)) {
                                break;
                            } elseif (count(array_filter($prototypes, fn(array $prototype) => $prototype['authors'][0] !== $this->event->pubkey)) > 0) {
                                break;
                            }



                            $removable_events = ($context->events)(Condition::makeFiltersFromPrototypes(...$prototypes));
                            foreach ($removable_events as $removable_event_id => $removable_event) {
                                unset($context->events[$removable_event_id]);
                            }
                            break;
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
