<?php

namespace Transpher\Nostr\Message;

/**
 * Description of Event
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Event {
    
    public function __construct(private \Transpher\Nostr\Event $event) {}
    public function __invoke(\Transpher\Key $private_key) : array {
        return ['EVENT', ($this->event)($private_key)];
    }
}
