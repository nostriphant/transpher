<?php

namespace rikmeijer\Transpher\Relay\Subscription;

use rikmeijer\Transpher\Nostr\Event;
use function Functional\some,
             Functional\partial_left,
             \Functional\map;

/**
 *
 * @author rmeijer
 */
class Condition {

    private function __construct(private string $type_test, private \Closure $test) {
        
    }
    public function __invoke(mixed $filter_field) : callable {
        if (($this->type_test)($filter_field) === false) {
            return fn() => true;
        }
        
        return partial_left($this->test, $filter_field);
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

    static function map(array $filter_prototypes) {
        return map($filter_prototypes, function (array $filter_prototype) {
            $available_conditions = [];
            foreach (glob(__DIR__ . '/Condition/*.php') as $available_filter_file) {
                $available_conditions[basename($available_filter_file, '.php')] = $available_filter_file;
            }
            return map(array_intersect_key($filter_prototype, $available_conditions), fn($condition, $filter_field) => (require $available_conditions[$filter_field])($condition));
        });
    }
}
