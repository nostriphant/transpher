<?php

namespace nostriphant\Transpher;

readonly class Alternate {

    private function __construct(
            public \Closure $callback
    ) {
        
    }

    static function __callStatic(string $name, array $arguments): self {
        return new self(fn(callable ...$callbacks) => yield from isset($callbacks[$name]) ? $callbacks[$name](...$arguments) : $callbacks['default'](...$arguments));
    }

    public function __invoke(callable ...$callbacks) {
        if (array_key_exists('default', $callbacks) === false) {
            $callbacks['default'] = fn() => [];
        }
        yield from ($this->callback)(...$callbacks);
    }
}
