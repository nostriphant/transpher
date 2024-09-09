<?php
namespace Transpher;

use Functional\Functional;


/**
 * Description of Filters
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Filters {
    
    static function constructSubscription(array $filter_prototype) : callable {
        $subscription_filters = \Functional\partial_left(Functional::some, array_map(fn(callable $possible_filter) => $possible_filter($filter_prototype), [
            self::scalar('ids', 'id'),
            self::scalar('kinds', 'kind'),
            self::scalar('authors', 'pubkey')
        ]));
            
        return fn(array $event) => $subscription_filters(fn($subscription_filter) => $subscription_filter($event));
    }
    
    static function scalar(string $filter_field, string $event_field) : callable {
        return function(array $filters) use ($filter_field, $event_field) : callable {
            if (array_key_exists($filter_field, $filters) === false) {
                return fn() => false;
            } elseif (is_array($filters[$filter_field]) === false) {
                return fn() => false;
            }
            return fn(array $event) => in_array($event[$event_field], $filters[$filter_field]);
        };
    }
}
