<?php

namespace rikmeijer\Transpher\Nostr;

class Message {

    public function __construct(private array $raw) {
        
    }

    public function __invoke(): array {
        return $this->raw;
    }

    public function __toString(): string {
        return \rikmeijer\Transpher\Nostr::encode($this());
    }
}
