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

    static function map(string $mapperClass, array $filter_prototype): callable {
        $conditions = [];
        foreach ($filter_prototype as $filter_field => $expected_value) {
            $method = $filter_field;
            if (isset(self::MAP[$method])) {
                $method = self::MAP[$method];
            }

            $conditions[$filter_field] = $mapperClass::$method($expected_value);
        }
        return $mapperClass::makeFilter(...$conditions);
    }
}
