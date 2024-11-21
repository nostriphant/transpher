<?php

namespace nostriphant\Transpher\Relay;

class Conditions {

    static function map(): callable {
        $directory = __DIR__ . '/Condition/';
        return function (array $filter_prototype) use ($directory): array {
            $conditions = [];
            foreach ($filter_prototype as $filter_field => $expected_value) {
                if (is_file($directory . $filter_field . '.php')) {
                    $condition = require __DIR__ . '/Condition/' . $filter_field . '.php';
                } else {
                    $condition = (require __DIR__ . '/Condition/tags.php')(ltrim($filter_field, '#'));
                }
                $conditions[$filter_field] = $condition($expected_value);
            }
            return $conditions;
        };
    }
}
