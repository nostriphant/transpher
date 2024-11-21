<?php
namespace nostriphant\Transpher\Nostr;

use function Functional\some,
             Functional\map,
             Functional\true;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use nostriphant\NIP01\Event;

readonly class Subscription {

    private function __construct(private array $filters) {
        
    }
    
    public function __invoke(Event $event) : bool {
        return some(array_map(fn(array $possible_filter) => true(array_map(fn(callable $subscription_filter) => $subscription_filter($event), $possible_filter)), $this->filters));
    }

    static function make(callable $to, array ...$filter_prototypes): self {
        return new self(array_map(fn(array $filter_prototype) => $to(Filter::fromPrototype($filter_prototype)), $filter_prototypes));
    }
}
