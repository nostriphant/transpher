<?php
namespace rikmeijer\Transpher\Relay;

use function Functional\some,
             Functional\map,
             Functional\true,
             Functional\partial_left,
             Functional\partial_right;
use rikmeijer\Transpher\Nostr\Subscription\Filter;
use rikmeijer\Transpher\Nostr\Event;

/**
 * Description of Filters
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Filters {

    private function __construct(private \Closure $possible_filters) {
        
    }
    
    public function __invoke(Event $event) : bool {
        return some(($this->possible_filters)(fn(array $possible_filter) => true(map($possible_filter, fn(callable $subscription_filter) => $subscription_filter($event)))));
    }

    static function make(array ...$filter_prototypes): self {
        return new self(partial_left('\Functional\map', map($filter_prototypes, fn(array $filter_prototype) => Filter::map($filter_prototype, Condition::map()))));
    }
}
