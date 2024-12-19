<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

class Test {

    private $test;

    private function __construct(callable $test) {
        $this->test = $test;
    }

    public function __invoke(array $where): array {
        return array_merge_recursive($where, call_user_func($this->test));
    }

    static function fake(): self {
        return new self(fn() => []);
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
        if (is_array($expected_value) === false) {
            return self::fake();
        }
        return new self(new Scalar($event_field, $expected_value));
    }

    static function until(mixed $expected_value): self {
        if (is_int($expected_value) === false) {
            return self::fake();
        }
        return new self(new Until($expected_value));
    }

    static function since(mixed $expected_value): self {
        if (is_int($expected_value) === false) {
            return self::fake();
        }
        return new self(new Since($expected_value));
    }

    static function tag(string $tag, mixed $expected_value): self {
        if (is_array($expected_value) === false) {
            return self::fake();
        }
        return new self(new Tag($tag, $expected_value));
    }

    static function limit(int $expected_value): Test {
        return self::fake();
    }

    static function __callStatic(string $name, array $arguments): self {
        return self::tag(ltrim($name, '#'), ...$arguments);
    }
}
