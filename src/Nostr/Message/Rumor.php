<?php

namespace nostriphant\Transpher\Nostr\Message;
use nostriphant\NIP01\Key;

readonly class Rumor {
    
    public function __construct(private \nostriphant\NIP59\Rumor $event) {}
    public function __invoke(Key $private_key) : array {
        return ['EVENT', get_object_vars(($this->event)($private_key))];
    }
}
