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

    static function scalar(string $event_field): callable {
        return self::build('is_array', fn(array $filter_values, Event $event): bool => in_array($event->$event_field, $filter_values));
    }

    static function since(string $event_field): callable {
        return self::build('is_int', fn(int $filter_value, Event $event): bool => $event->$event_field >= $filter_value);
    }

    static function until(string $event_field): callable {
        return self::build('is_int', fn(int $filter_value, Event $event): bool => $event->$event_field <= $filter_value);
    }

    static function tag(string $event_tag_identifier): callable {
        return self::build('is_array', fn(array $filter_values, Event $event): bool => some($event->tags, fn(array $event_tag) => $event_tag[0] === $event_tag_identifier && in_array($event_tag[1], $filter_values)));
    }

    static function limit(): callable {
        $hits = 0;
        return self::build('is_int', function (int $limit, Event $event) use (&$hits) {
                    $hits++;
                    return $limit >= $hits;
                });
    }

    static function build(string $type_test, \Closure $test): callable {
        return function (mixed $expected_value) use ($type_test, $test): self {
            if ($type_test($expected_value) === false) {
                return new self(fn(Event $event) => true);
            }

            return new self(partial_left($test, $expected_value));
        };
    }
}
