<?php

namespace nostriphant\Transpher\Relay;

readonly class Conditions {

    static function createFromPrototypes(string $mapperClass, array $filter_prototypes) {
        return fn(callable $executeCondition) => array_map(
                        $executeCondition,
                        array_map(
                                fn(array $filter_prototype) => array_map(
                                        fn(string $method, mixed $expected_value) => $mapperClass::$method($expected_value),
                                        array_keys($filter_prototype),
                                        $filter_prototype
                                ),
                                $filter_prototypes
                        ));
    }
}
