<?php

namespace nostriphant\Transpher\Relay\Incoming;

readonly class Alternate {

    private function __construct(
            public \Closure $callback
    ) {
        
    }

    static function __callStatic(string $name, array $arguments): self {
        return new self(fn(callable ...$callbacks) => yield from $callbacks[$name](...$arguments));
    }

    public function __invoke(callable ...$callbacks) {
        yield from ($this->callback)(...$callbacks);
    }
}
