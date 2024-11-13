<?php

namespace nostriphant\Transpher\Relay\Incoming;

readonly class Constraint {

    private function __construct(
            public Constraint\Result $result,
            public \Closure $callback,
            public ?string $reason = null,
    ) {
        
    }

    static function accept(mixed ...$args): self {
        return new self(Constraint\Result::ACCEPTED, (fn(array $callbacks) => yield from $callbacks['accepted'](...$args)));
    }

    static function reject(string $reason): self {
        return new self(Constraint\Result::REJECTED, (fn(array $callbacks) => yield from $callbacks['rejected']($reason)), $reason);
    }

    public function __invoke(callable ...$callbacks) {
        yield from ($this->callback)($callbacks);
    }
}
