<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Event;

readonly class Condition {

    public function __construct(private Condition\Test $test) {
        
    }

    public function __invoke(Event $event): bool {
        return call_user_func($this->test, $event);
    }

    static function scalar(string $event_field, mixed $expected_value): self {
        return new self(new Condition\Scalar($event_field, $expected_value));
    }

    static function since(string $event_field, mixed $expected_value): self {
        return new self(new Condition\Since($event_field, $expected_value));
    }

    static function until(string $event_field, mixed $expected_value): self {
        return new self(new Condition\Until($event_field, $expected_value));
    }

    static function tag(string $event_tag_identifier, mixed $expected_value): self {
        return new self(new Condition\Tag($event_tag_identifier, $expected_value));
    }

    static function limit(int $expected_value): self {
        return new self(new Condition\Limit($expected_value));
    }
}
