<?php

namespace nostriphant\Transpher\Relay\Incoming;

readonly class Constraint {

    private function __construct(
            public Constraint\Result $result,
            public ?string $reason = null
    ) {
        
    }

    static function accept(): self {
        return new self(Constraint\Result::ACCEPTED);
    }

    static function reject(string $reason): self {
        return new self(Constraint\Result::REJECTED, $reason);
    }
}
