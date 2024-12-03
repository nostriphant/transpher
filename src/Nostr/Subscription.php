<?php
namespace nostriphant\Transpher\Nostr;

use function Functional\some;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use nostriphant\Transpher\Relay\Conditions;
use nostriphant\NIP01\Event;

readonly class Subscription {

    public bool $enabled;
    private array $filters;

    private function __construct(public array $filter_prototypes) {
        $this->enabled = array_reduce($this->filter_prototypes, fn(bool $enabled, array $filter_prototype) => $enabled === false || empty($filter_prototype) === false, true);
        $to = new Conditions(\nostriphant\Transpher\Relay\Condition::class);
        $this->filters = $this->enabled ? array_map(fn(array $filter_prototype) => Filter::fromPrototype(...$to($filter_prototype)), $filter_prototypes) : [];
    }
    
    public function __invoke(Event $event): ?bool {
        return $this->enabled ? some(array_map(fn(Filter $filter) => $filter($event), $this->filters)) : null;
    }

    static function make(array ...$filter_prototypes): self {
        return new self($filter_prototypes);
    }
}
