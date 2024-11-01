<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\Transpher\Nostr\Event;
use function Functional\some,
             Functional\partial_left;

class Condition {

    private function __construct(private string $type_test, private \Closure $test) {
        
    }
    public function __invoke(mixed $filter_value): callable {
        if (($this->type_test)($filter_value) === false) {
            return fn() => true;
        }
        
        return partial_left($this->test, $filter_value);
    }
    
    
    static function scalar(string $event_field) : callable {
        return new self('is_array', fn(array $filter_values, Event $event) => in_array($event->$event_field, $filter_values));
    }
    static function since(string $event_field) : callable {
        return new self('is_int', fn(int $filter_value, Event $event) : bool => $event->$event_field >= $filter_value);
    }
    static function until(string $event_field) : callable {
        return new self('is_int', fn(int $filter_value, Event $event) : bool => $event->$event_field <= $filter_value);
    }
    static function tag(string $event_tag_identifier) : callable {
        return new self('is_array', fn(array $filter_values, Event $event) : bool => some($event->tags, fn(array $event_tag) => $event_tag[0] === $event_tag_identifier && in_array($event_tag[1], $filter_values)));
    }
    static function limit() {
        $hits = 0;
        return new self('is_int', function(int $limit, Event $event) use (&$hits) {
            $hits++;
            return $limit >= $hits;
        });
    }

    static function map(): callable {
        return function (mixed $filter_value, string $filter_field) {
            $directory = __DIR__ . '/Condition/';

            if (is_file($directory . $filter_field . '.php')) {
                $filter = require __DIR__ . '/Condition/' . $filter_field . '.php';
            } else {
                $filter = (require __DIR__ . '/Condition/tags.php')(ltrim($filter_field, '#'));
            }

            return $filter($filter_value);
        };
    }

    static function makeFiltersFromPrototypes(array ...$prototypes): \nostriphant\Transpher\Nostr\Filters {
        return \nostriphant\Transpher\Nostr\Filters::make(self::map(), ...$prototypes);
    }
}
