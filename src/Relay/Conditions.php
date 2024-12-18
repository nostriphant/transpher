<?php

namespace nostriphant\Transpher\Relay;

readonly class Conditions {

    public function __construct(private array $filter_prototypes) {

    }

    public function __invoke(ConditionFactory $conditionFactory, callable $executeCondition): array {
        return array_map(
                $executeCondition,
                array_map(
                        fn(array $filter_prototype) => array_map(
                                $conditionFactory,
                                array_keys($filter_prototype),
                                $filter_prototype
                        ),
                        $this->filter_prototypes
                ));
    }
}
