<?php

namespace nostriphant\Transpher\Relay\Incoming;

readonly class Constraint {

    private function __construct(
            public \Closure $callback
    ) {
        
    }

    static function accepted(mixed ...$args): self {
        return new self(fn(callable ...$callbacks) => yield from $callbacks['accepted'](...$args));
    }

    static function rejected(string $reason): self {
        return new self(fn(callable ...$callbacks) => yield from $callbacks['rejected']($reason));
    }

    public function __invoke(callable ...$callbacks) {
        yield from ($this->callback)(...$callbacks);
    }
}
