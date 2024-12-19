<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

class Test {

    private $test;

    private function __construct(callable $test) {
        $this->test = $test;
    }

    public function __invoke(array $query): array {
        return call_user_func($this->test, $query);
    }

    static function authors(mixed $expected_value): self {
        return self::scalar('pubkey', $expected_value);
    }

    static function ids(mixed $expected_value): self {
        return self::scalar('id', $expected_value);
    }

    static function kinds(mixed $expected_value): self {
        return self::scalar('kind', $expected_value);
    }

    static function scalar(string $event_field, mixed $expected_value): self {
        return new Test(new Scalar($event_field, $expected_value));
    }

    static function until(mixed $expected_value): self {
        return new Test(new Until($expected_value));
    }

    static function since(mixed $expected_value): self {
        return new Test(new Since($expected_value));
    }

    static function tag(string $tag, mixed $expected_value): self {
        return new Test(new Tag($tag, $expected_value));
    }

    static function limit(int $expected_value): self {
        return new Test(new Limit($expected_value));
    }

    static function __callStatic(string $name, array $arguments): self {
        return self::tag(ltrim($name, '#'), ...$arguments);
    }
}
