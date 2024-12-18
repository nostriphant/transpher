<?php
namespace nostriphant\Transpher\Nostr;

readonly class Subscription {

    private \Closure $test;

    public function __construct(private array $filter_prototypes, string $mapperClass) {
        $this->test = $mapperClass::makeConditions($filter_prototypes);
    }
    
    public function __invoke() {
        return call_user_func_array($this->test, func_get_args());
    }
}
