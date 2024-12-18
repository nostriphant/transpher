<?php

namespace nostriphant\Transpher\Relay;

readonly class Conditions {

    static function createFromPrototypes(string $mapperClass, array $filter_prototypes) {
        $mapper = function (array $filter_prototype) use ($mapperClass) {
            $conditions = [];
            foreach ($filter_prototype as $filter_field => $expected_value) {
                $conditions[$filter_field] = $mapperClass::$filter_field($expected_value);
            }
            return $conditions;
        };
        return fn(callable $executeCondition) => array_map($executeCondition, array_map($mapper, $filter_prototypes));
    }
}
