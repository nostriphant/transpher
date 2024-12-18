<?php

namespace nostriphant\Transpher\Relay;

readonly class Conditions {

    const array MAP = [
        'authors' => 'pubkey',
        'ids' => 'id',
        'kinds' => 'kind',
        'limit' => 'limit',
        'since' => 'since',
        'until' => 'until'
    ];

    static function createFromPrototypes(string $mapperClass, array $filter_prototypes) {
        $mapper = function (array $filter_prototype) use ($mapperClass) {
            $conditions = [];
            foreach ($filter_prototype as $filter_field => $expected_value) {
                $method = $filter_field;
                if (isset(self::MAP[$method])) {
                    $method = self::MAP[$method];
                }

                $conditions[$filter_field] = $mapperClass::$method($expected_value);
            }
            return $conditions;
        };
        return array_map($mapper, $filter_prototypes);
    }
}
