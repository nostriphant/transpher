<?php

namespace nostriphant\Transpher\Relay;

readonly class Conditions {

    static function createFromPrototypes(callable $conditionFactory, array $filter_prototypes) {
        return fn(callable $executeCondition) => array_map(
                        $executeCondition,
                        array_map(
                                fn(array $filter_prototype) => array_map(
                                        $conditionFactory,
                                        array_keys($filter_prototype),
                                        $filter_prototype
                                ),
                                $filter_prototypes
                        ));
    }
}
