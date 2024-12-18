<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Event;

readonly class Condition {

    public function __construct(private Condition\Test $test) {
        
    }

    public function __invoke(Event $event): bool {
        return call_user_func($this->test, $event);
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
        return new self(new Condition\Scalar($event_field, $expected_value));
    }

    static function until(mixed $expected_value): self {
        return new self(new Condition\Until('created_at', $expected_value));
    }

    static function since(mixed $expected_value): self {
        return new self(new Condition\Since('created_at', $expected_value));
    }

    static function tag(string $tag, mixed $expected_value): self {
        return new self(new Condition\Tag($tag, $expected_value));
    }

    static function limit(int $expected_value): self {
        return new self(new Condition\Limit($expected_value));
    }

    static function __callStatic(string $name, array $arguments): self {
        return self::tag(ltrim($name, '#'), ...$arguments);
    }

    static function makeConditions(Conditions $conditions): callable {
        $conditionsFactory = $conditions(new \nostriphant\Transpher\Relay\ConditionFactory(self::class));
        $conditions = $conditionsFactory(fn(array $conditions) => fn(Event $event): bool => array_reduce($conditions, fn(bool $result, self $condition) => $result && $condition($event), true));
        return fn(Event $event): bool => array_reduce($conditions, fn(bool $result, callable $filter) => $result || $filter($event), false);
    }
}
