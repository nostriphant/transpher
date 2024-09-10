<?php
namespace Transpher;

use Functional\Functional;
use function Functional\partial_left, Functional\some, Functional\filter;

/**
 * Description of Filters
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Filters {
    
    static function constructSubscription(array $filter_prototype) : callable {
        $subscription_filters = partial_left(Functional::some, array_map(fn(callable $possible_filter) => $possible_filter($filter_prototype), [
            self::make('ids', self::scalar('id')),
            self::make('kinds', self::scalar('kind')),
            self::make('authors', self::scalar('pubkey')),
            self::make('#p', self::tag('p'))
        ]));
            
        return fn(array $event) => $subscription_filters(fn($subscription_filter) => $subscription_filter($event));
    }
    
    static function skipFilter(array $filters, string $filter_field) {
        if (array_key_exists($filter_field, $filters) === false) {
            return true;
        } elseif (is_array($filters[$filter_field]) === false) {
            return true;
        }
        return false;
    }
    
    static function make(string $filter_field, callable $event_test) : callable {
        return function(array $filters) use ($filter_field, $event_test) : callable {
            if (self::skipFilter($filters, $filter_field)) {
                return fn() => false;
            }
            return partial_left($event_test, $filters[$filter_field]);
        };
    }
    
    static function scalar(string|int $event_field) {
        return fn(array $filter_values, array $event) => in_array($event[$event_field], $filter_values);
    }
    static function tag(string $event_tag_identifier) {
        $tag_value_test = self::scalar(1);
        return fn(array $filter_values, array $event) => some($event['tags'], fn(array $event_tag) => $event_tag[0] === $event_tag_identifier && $tag_value_test($filter_values, $event_tag));
    }
}
