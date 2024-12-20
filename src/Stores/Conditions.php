<?php

namespace nostriphant\Transpher\Stores;

readonly class Conditions {

    public function __construct(public array $filter_prototypes) {
        
    }

    public function __invoke(ConditionFactory $conditionFactory): array {
        return array_map(fn(array $filter_prototype) => array_map(
                        $conditionFactory,
                        array_keys($filter_prototype),
                        $filter_prototype
                ),
                $this->filter_prototypes
        );
    }
}
