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

    public function __construct(private string $mapperClass) {
        
    }

    public function __invoke(array $filter_prototype): array {
        $conditions = [];
        foreach ($filter_prototype as $filter_field => $expected_value) {
            $method = $filter_field;
            if (isset(self::MAP[$method])) {
                $method = self::MAP[$method];
            }

            $conditions[$filter_field] = $this->mapperClass::$method($expected_value);
        }
        return $conditions;
    }
}
