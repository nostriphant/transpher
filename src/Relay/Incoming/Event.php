<?php

namespace rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Subscriptions;
use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\Relay\Store;
use rikmeijer\Transpher\Nostr\Event\KindClass;
use rikmeijer\Transpher\Relay\Condition;

/**
 * Description of Event
 *
 * @author rmeijer
 */
readonly class Event implements \rikmeijer\Transpher\Relay\Incoming {

    public function __construct(private \rikmeijer\Transpher\Nostr\Event $event) {
        
    }

    #[\Override]
    static function fromMessage(array $message): self {
        return new self(new \rikmeijer\Transpher\Nostr\Event(...$message[1]));
    }

    #[\Override]
    public function __invoke(): callable {
        return function (Store $events): \Generator {
            $replaceable_events = [];
            switch (\rikmeijer\Transpher\Nostr\Event::determineClass($this->event)) {
                case KindClass::REGULAR:
                    $events[$this->event->id] = $this->event;
                    break;

                case KindClass::REPLACEABLE:
                    $replaceable_events = $events(Condition::makeFiltersFromPrototypes([
                        'kinds' => [$this->event->kind],
                        'authors' => [$this->event->pubkey]
                    ]));

                    $events[$this->event->id] = $this->event;
                    foreach ($replaceable_events as $replaceable_event) {
                        $replace_id = $replaceable_event->id;
                        if ($replaceable_event->created_at === $this->event->created_at) {
                            $replace_id = max($replaceable_event->id, $this->event->id);
                        }
                        unset($events[$replace_id]);
                    }
                    break;

                case KindClass::EPHEMERAL:
                    break;

                case KindClass::ADDRESSABLE:
                    $replaceable_events = $events(Condition::makeFiltersFromPrototypes([
                                'kinds' => [$this->event->kind],
                                'authors' => [$this->event->pubkey],
                                '#d' => \rikmeijer\Transpher\Nostr\Event::extractTagValues($this->event, 'd')
                    ]));

                    $events[$this->event->id] = $this->event;
                    foreach ($replaceable_events as $replaceable_event) {
                        unset($events[$replaceable_event->id]);
                    }
                    break;

                case KindClass::UNDEFINED:
                default:
                    yield Factory::notice('Undefined event kind ' . $this->event->kind);
                    return;
            }
            Subscriptions::apply($this->event);
            yield Factory::accept($this->event->id);
        };
    }
}
