<?php
namespace nostriphant\Transpher\Nostr;

use function Functional\some;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use nostriphant\Transpher\Relay\Conditions;
use nostriphant\NIP01\Event;

readonly class Subscription {

    public bool $disabled;
    private array $filters;

    public function __construct(public array $filter_prototypes) {
        $this->disabled = empty($filter_prototypes) || array_reduce($filter_prototypes, fn(bool $disabled, array $filter_prototype) => empty($filter_prototype), true);

        //var_Dump($this->disabled, $filter_prototypes);

        $to = new Conditions(\nostriphant\Transpher\Relay\Condition::class);
        $this->filters = $this->disabled ? [] : array_map(fn(array $filter_prototype) => Filter::fromPrototype(...$to($filter_prototype)), $filter_prototypes);
    }
    
    public function __invoke(Event $event): ?bool {
        return $this->disabled ? null : some(array_map(fn(Filter $filter) => $filter($event), $this->filters));
    }
}
