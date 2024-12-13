<?php
namespace nostriphant\Transpher\Nostr;

use function Functional\some;
use nostriphant\Transpher\Relay\Conditions;
use nostriphant\NIP01\Event;

readonly class Subscription {

    public function __construct(public bool $disabled, private array $filters) {
        
    }
    
    public function __invoke(Event $event): ?bool {
        return $this->disabled ? null : some(array_map(fn(callable $filter) => $filter($event), $this->filters));
    }

    static function make(array $filter_prototypes, Conditions $to): self {
        $disabled = empty($filter_prototypes) || array_reduce($filter_prototypes, fn(bool $disabled, array $filter_prototype) => empty($filter_prototype), true);
        $filters = $to->filters($filter_prototypes);
        return new self($disabled, $filters);
    }
}
