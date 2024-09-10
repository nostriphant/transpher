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
            self::scalar('ids', 'id'),
            self::scalar('kinds', 'kind'),
            self::scalar('authors', 'pubkey'),
            self::tag('#p', 'p')
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
    
    static function buildFilter(string $filter_field, callable $event_test) : callable {
        return function(array $filters) use ($filter_field, $event_test) : callable {
            if (self::skipFilter($filters, $filter_field)) {
                return fn() => false;
            }
            return partial_left($event_test, $filters[$filter_field]);
        };
    }
    
    static function scalar(string $filter_field, string $event_field) : callable {
        return self::buildFilter($filter_field, fn(array $filter_values, array $event) => in_array($event[$event_field], $filter_values));
    }
    
    static function tag(string $filter_tag_identifier, string $event_tag_identifier) : callable {
        return self::buildFilter($filter_tag_identifier, fn(array $filter_values, array $event) => some($event['tags'], fn(array $event_tag) => $event_tag[0] === $event_tag_identifier && in_array($event_tag[1], $filter_values)));
    }
}
