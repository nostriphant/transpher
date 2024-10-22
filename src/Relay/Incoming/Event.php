<?php

namespace rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Subscriptions;
use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\Relay\Store;
use rikmeijer\Transpher\Nostr\Event\KindClass;

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
        return function (array|Store $events): \Generator {
            switch (\rikmeijer\Transpher\Nostr\Event::determineClass($this->event)) {
                case KindClass::EPHEMERAL:
                    break;
                default:
                    $events[$this->event->id] = $this->event;
                    break;
            }
            Subscriptions::apply($this->event);
            yield Factory::accept($this->event->id);
        };
    }
}
