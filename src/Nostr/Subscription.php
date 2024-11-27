<?php
namespace nostriphant\Transpher\Nostr;

use function Functional\some;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use nostriphant\Transpher\Relay\Conditions;
use nostriphant\NIP01\Event;

readonly class Subscription {

    private array $filters;

    private function __construct(public Conditions $to, public array $filter_prototypes) {
        $this->filters = $filters = array_map(fn(array $filter_prototype) => Filter::fromPrototype(...$to($filter_prototype)), $filter_prototypes);
    }
    
    public function __invoke(Event $event): bool {
        return some(array_map(fn(Filter $filter) => $filter($event), $this->filters));
    }

    static function make(Conditions $to, array ...$filter_prototypes): self {
        return new self($to, $filter_prototypes);
    }
}
