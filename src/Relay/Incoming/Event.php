<?php

namespace rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Subscriptions;
use rikmeijer\Transpher\Nostr\Message;
use rikmeijer\Transpher\Relay\Store;

/**
 * Description of Event
 *
 * @author rmeijer
 */
readonly class Event {

    public function __construct(private \rikmeijer\Transpher\Nostr\Event $event) {
        
    }

    static function fromMessage(array $message): self {
        return new self(new \rikmeijer\Transpher\Nostr\Event(...$message[1]));
    }

    public function __invoke(array|Store $events): \Generator {
        $events[] = $this->event;
        Subscriptions::apply($this->event);
        yield Message::accept($this->event->id);
    }
}
