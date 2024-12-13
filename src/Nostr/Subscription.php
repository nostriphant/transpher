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

    static function disabled(array $filter_prototypes): bool {
        return empty($filter_prototypes) || array_reduce($filter_prototypes, fn(bool $disabled, array $filter_prototype) => empty($filter_prototype), true);
    }

    static function make(array $filter_prototypes, string $mapperClass): self {
        if (self::disabled($filter_prototypes)) {
            return new self(fn() => null);
        }

        $mapper = new Conditions($mapperClass);
        return new self($mapper($filter_prototypes));
    }
}
