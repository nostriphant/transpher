<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Relay\Condition\Test;

readonly class Condition {

    public function __invoke(array $conditions) {
        return fn(Event $event): bool => array_reduce($conditions, fn(bool $result, Test $condition) => $result && $condition($event), true);
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
        return new Condition\Scalar($event_field, $expected_value);
    }

    static function until(mixed $expected_value): Test {
        return new Condition\Until($expected_value);
    }

    static function since(mixed $expected_value): Test {
        return new Condition\Since($expected_value);
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

    static function makeConditions(Conditions $conditionsFactory): callable {
        $conditions = array_map(
                new self(),
                $conditionsFactory(new ConditionFactory(self::class))
        );
        return fn(Event $event): bool => array_reduce($conditions, fn(bool $result, callable $filter) => $result || $filter($event), false);
    }
}
