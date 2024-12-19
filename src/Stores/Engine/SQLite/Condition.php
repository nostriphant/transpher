<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite;

use nostriphant\Transpher\Stores\Engine\SQLite\Condition\Test;

readonly class Condition {

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
        return new Condition\Scalar($event_field, $expected_value);
    }

    static function until(mixed $expected_value): Test {
        return new Condition\Until('created_at', $expected_value);
    }

    static function since(mixed $expected_value): Test {
        return new Condition\Since('created_at', $expected_value);
    }

    static function tag(string $tag, mixed $expected_value): Test {
        return new Condition\Tag($tag, $expected_value);
    }

    static function limit(int $expected_value): Test {
        return new Condition\Limit($expected_value);
    }

    static function __callStatic(string $name, array $arguments): Test {
        return self::tag(ltrim($name, '#'), ...$arguments);
    }
}
