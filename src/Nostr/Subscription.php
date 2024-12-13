<?php
namespace nostriphant\Transpher\Nostr;

use function Functional\some;
use nostriphant\Transpher\Relay\Conditions;
use nostriphant\NIP01\Event;

readonly class Subscription {

    public function __construct(private \Closure $test) {
        
    }
    
    public function __invoke() {
        return call_user_func_array($this->test, func_get_args());
    }

    static function make(array $filter_prototypes, string $mapperClass): self {
        $disabled = empty($filter_prototypes) || array_reduce($filter_prototypes, fn(bool $disabled, array $filter_prototype) => empty($filter_prototype), true);
        if ($disabled) {
            return new self(fn() => null);
        }

        $filters = array_map(fn(array $filter_prototype) => Conditions::map($mapperClass, $filter_prototype), $filter_prototypes);
        return new self($mapperClass::wrapFilters($filters));
    }
}
