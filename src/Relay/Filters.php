<?php
namespace rikmeijer\Transpher\Relay;

use function Functional\some,
             Functional\map,
             Functional\true,
             Functional\partial_left;
use rikmeijer\Transpher\Nostr\Subscription\Filter;
use rikmeijer\Transpher\Nostr\Event;
use rikmeijer\Transpher\Relay\Subscription\Condition;

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
        return new self(partial_left('\Functional\map', map($filter_prototypes, function (array $filter_prototype) {
            return Filter::fromPrototype($filter_prototype);
                        })));
    }
}
