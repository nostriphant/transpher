<?php
namespace nostriphant\Transpher\Nostr;

use function Functional\some;
use nostriphant\Transpher\Relay\Conditions;
use nostriphant\NIP01\Event;

readonly class Subscription {

    private \Closure $test;

    public function __construct(array $filter_prototypes, string $mapperClass) {
        if (self::disabled($filter_prototypes)) {
            $this->test = fn() => null;
        } else {
            $mapper = new Conditions($mapperClass);
            $this->test = $mapper($filter_prototypes);
        }
    }
    
    public function __invoke() {
        return call_user_func_array($this->test, func_get_args());
    }

    static function disabled(array $filter_prototypes): bool {
        return empty($filter_prototypes) || array_reduce($filter_prototypes, fn(bool $disabled, array $filter_prototype) => empty($filter_prototype), true);
    }
}
