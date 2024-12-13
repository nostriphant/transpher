<?php
namespace nostriphant\Transpher\Nostr;

readonly class Subscription {

    private \Closure $test;

    public function __construct(private array $filter_prototypes, string $mapperClass) {
        $this->test = self::disabled($this->filter_prototypes) ? fn() => null : $mapperClass::makeConditions($filter_prototypes);
    }
    
    public function __invoke() {
        return call_user_func_array($this->test, func_get_args());
    }

    static function disabled(array $filter_prototypes): bool {
        return empty($filter_prototypes) || array_reduce($filter_prototypes, fn(bool $disabled, array $filter_prototype) => empty($filter_prototype), true);
    }
}
