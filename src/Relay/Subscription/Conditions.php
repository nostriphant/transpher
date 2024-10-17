<?php

namespace rikmeijer\Transpher\Relay\Subscription;

use function \Functional\map;

readonly class Conditions {

    static function map(array $filter_prototypes) {
        return map($filter_prototypes, function (array $filter_prototype) {
            $available_conditions = [];
            foreach (glob(__DIR__ . '/Conditions/*.php') as $available_filter_file) {
                $available_conditions[basename($available_filter_file, '.php')] = $available_filter_file;
            }
            return map(array_intersect_key($filter_prototype, $available_conditions), fn($condition, $filter_field) => (require $available_conditions[$filter_field])($condition));
        });
    }
}
