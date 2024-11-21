<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Event;
use function Functional\some,
             Functional\partial_left;

readonly class Condition {

    public function __construct(private \Closure $test) {
        
    }

    public function __invoke(Event $event): bool {
        return call_user_func($this->test, $event);
    }

    static function scalar(string $event_field, mixed $expected_value): self {
        return new self(fn(Event $event): bool => is_array($expected_value) === false || in_array($event->$event_field, $expected_value));
    }

    static function since(string $event_field, mixed $expected_value): self {
        return new self(fn(Event $event): bool => is_int($expected_value) === false || $event->$event_field >= $expected_value);
    }

    static function until(string $event_field, mixed $expected_value): self {
        return new self(fn(Event $event): bool => is_int($expected_value) === false || $event->$event_field <= $expected_value);
    }

    static function tag(string $event_tag_identifier, mixed $expected_value): self {
        return new self(fn(Event $event): bool => is_array($expected_value) === false || some($event->tags, fn(array $event_tag) => $event_tag[0] === $event_tag_identifier && in_array($event_tag[1], $expected_value)));
    }

    static function limit(int $expected_value): self {
        $hits = 0;
        return new self(function (Event $event) use (&$hits, $expected_value) {
                    $hits++;
                    return is_int($expected_value) === false || $expected_value >= $hits;
                });
    }
}
