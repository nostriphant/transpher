<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Event;
use function Functional\some,
             Functional\partial_left;

class Conditions {

    static function build(string $type_test, \Closure $test): callable {
        return function (mixed $expected_value) use ($type_test, $test): Condition {
            if ($type_test($expected_value) === false) {
                return new Condition(fn(Event $event) => true);
            }

            return new Condition(partial_left($test, $expected_value));
        };
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

    static function map(): callable {
        $directory = __DIR__ . '/Condition/';
        return function (array $filter_prototype) use ($directory): array {
            $conditions = [];
            foreach ($filter_prototype as $filter_field => $expected_value) {
                if (is_file($directory . $filter_field . '.php')) {
                    $condition = require __DIR__ . '/Condition/' . $filter_field . '.php';
                } else {
                    $condition = (require __DIR__ . '/Condition/tags.php')(ltrim($filter_field, '#'));
                }
                $conditions[$filter_field] = $condition($expected_value);
            }
            return $conditions;
        };
    }
}
