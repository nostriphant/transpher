<?php
namespace Transpher;

use function Functional\partial_left, Functional\some, \Functional\map, \Functional\true;
use \Transpher\Nostr\Event\Signed;

/**
 * Description of Filters
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Filters implements Nostr\Relay\Filter {
    
    private array $possible_filters;
    
    public function __construct(array $filter_prototype) {
        $this->possible_filters = array_map(fn(callable $possible_filter) => $possible_filter($filter_prototype), [
            self::make('ids', self::scalar('id')),
            self::make('kinds', self::scalar('kind')),
            self::make('authors', self::scalar('pubkey')),
            self::make('since', self::since('created_at')),
            self::make('until', self::until('created_at')),
            self::make('#p', self::tag('p')),
            self::make('#e', self::tag('e')),
            self::make('limit', self::limit())
        ]);
    }
    
    public function __invoke(Signed $event) : bool {
        return true(map($this->possible_filters, fn($subscription_filter) => $subscription_filter($event)));
    }
    
    static function invalid(array $filters, string $filter_field, callable $type_test) {
        if (array_key_exists($filter_field, $filters) === false) {
            return true;
        } elseif ($type_test($filters[$filter_field]) === false) {
            return true;
        }
        return false;
    }
    
    static function make(string $filter_field, callable $event_test) : callable {
        return function(array $filters) use ($filter_field, $event_test) : callable {
            $filter_type = (string)(new \ReflectionFunction($event_test))->getParameters()[0]->getType();
            
            if (self::invalid($filters, $filter_field, 'is_' . $filter_type)) {
                return fn() => true;
            }
            return partial_left($event_test, $filters[$filter_field]);
        };
    }
    
    static function scalar(string|int $event_field) : callable {
        return fn(array $filter_values, Signed $event) : bool => in_array($event->$event_field, $filter_values);
    }
    static function since(string $event_field) : callable {
        return fn(int $filter_value, Signed $event) : bool => $event->$event_field >= $filter_value;
    }
    static function until(string $event_field) : callable {
        return fn(int $filter_value, Signed $event) : bool => $event->$event_field <= $filter_value;
    }
    static function tag(string $event_tag_identifier) : callable {
        return fn(array $filter_values, Signed $event) : bool => some($event->tags, fn(array $event_tag) => $event_tag[0] === $event_tag_identifier && in_array($event_tag[1], $filter_values));
    }
    static function limit() {
        $hits = 0;
        return function(int $limit, Signed $event) use (&$hits) {
            $hits++;
            return $limit >= $hits;
        };
    }
}
