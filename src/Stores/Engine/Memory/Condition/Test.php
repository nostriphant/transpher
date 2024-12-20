<?php

namespace nostriphant\Transpher\Stores\Engine\Memory\Condition;

use nostriphant\NIP01\Event;

class Test {

    private $test;

    private function __construct(callable $test) {
        $this->test = $test;
    }

    public function __invoke(Event $event): bool {
        return call_user_func($this->test, $event);
    }

    static function fake(): self {
        return new self(fn() => true);
    }

    static function authors(mixed $expected_value): Test {
        return self::scalar('pubkey', $expected_value);
    }

    static function ids(mixed $expected_value): Test {
        return self::scalar('id', $expected_value);
    }

    static function kinds(mixed $expected_value): Test {
        return self::scalar('kind', $expected_value);
    }

    static function scalar(string $event_field, mixed $expected_value): Test {
        if (is_array($expected_value) === false) {
            return self::fake();
        }
        return new self(new Scalar($event_field, $expected_value));
    }

    static function until(mixed $expected_value): Test {
        if (is_int($expected_value) === false) {
            return self::fake();
        }
        return new self(new Until($expected_value));
    }

    static function since(mixed $expected_value): Test {
        if (is_int($expected_value) === false) {
            return self::fake();
        }
        return new self(new Since($expected_value));
    }

    static function tag(string $tag, mixed $expected_value): Test {
        if (is_array($expected_value) === false) {
            return self::fake();
        }
        return new self(new Tag($tag, $expected_value));
    }

    static function limit(int $expected_value): Test {
        return new self(fn() => true);
    }

    static function __callStatic(string $name, array $arguments): Test {
        return self::tag(ltrim($name, '#'), ...$arguments);
    }
}
