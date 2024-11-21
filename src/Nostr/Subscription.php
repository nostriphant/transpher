<?php
namespace nostriphant\Transpher\Nostr;

use function Functional\some;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use nostriphant\NIP01\Event;

readonly class Subscription {

    private function __construct(private array $filters) {
        
    }
    
    public function __invoke(Event $event) : bool {
        return some(array_map(fn(Filter $filter) => $filter($event), $this->filters));
    }

    static function make(callable $to, array ...$filter_prototypes): self {
        return new self(array_map(fn(array $filter_prototype) => Filter::fromPrototype(...$to($filter_prototype)), $filter_prototypes));
    }
}
