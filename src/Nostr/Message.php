<?php

namespace rikmeijer\Transpher\Nostr;

/**
 *
 * @author hello@rikmeijer.nl
 */
class Message {

    public function __construct(private array $raw) {
        
    }

    public function __toString(): string {
        return \rikmeijer\Transpher\Nostr::encode($this->raw);
    }
}
