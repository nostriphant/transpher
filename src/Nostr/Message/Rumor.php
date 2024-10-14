<?php

namespace rikmeijer\Transpher\Nostr\Message;
use rikmeijer\Transpher\Nostr\Key;

/**
 * Description of Event
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Rumor {
    
    public function __construct(private \rikmeijer\Transpher\Nostr\Rumor $event) {}
    public function __invoke(Key $private_key) : array {
        return ['EVENT', get_object_vars(($this->event)($private_key))];
    }
}
