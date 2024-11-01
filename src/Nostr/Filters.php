<?php
namespace nostriphant\Transpher\Nostr;

use function Functional\some,
             Functional\map,
             Functional\true,
             Functional\partial_left;
use nostriphant\Transpher\Nostr\Subscription\Filter;

readonly class Filters {

    private function __construct(private \Closure $possible_filters) {
        
    }
    
    public function __invoke(Event $event) : bool {
        return some(($this->possible_filters)(fn(array $possible_filter) => true(map($possible_filter, fn(callable $subscription_filter) => $subscription_filter($event)))));
    }

    static function make(callable $to, array ...$filter_prototypes): self {
        return new self(partial_left('\Functional\map', map($filter_prototypes, Filter::map($to))));
    }
}
