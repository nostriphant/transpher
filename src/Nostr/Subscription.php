<?php
namespace nostriphant\Transpher\Nostr;

use function Functional\some;
use nostriphant\Transpher\Relay\Conditions;
use nostriphant\NIP01\Event;

readonly class Subscription {

    public function __construct(public bool $disabled, private \Closure $test) {
        
    }
    
    public function __invoke() {
        return $this->disabled ? null : call_user_func_array($this->test, func_get_args());
    }

    static function make(array $filter_prototypes, string $mapperClass): self {
        $to = new Conditions($mapperClass);
        $disabled = empty($filter_prototypes) || array_reduce($filter_prototypes, fn(bool $disabled, array $filter_prototype) => empty($filter_prototype), true);
        $filters = $to->filters($filter_prototypes);
        return new self($disabled, $mapperClass::wrapFilters($filters));
    }
}
