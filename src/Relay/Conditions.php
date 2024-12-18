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


    public function mapPrototypeToConditions(array $filter_prototype) {
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

    public function __invoke(array $filter_prototypes, callable $makeFilter): array {
        $filters = [];
        foreach ($filter_prototypes as $filter_prototype) {
            $filters[] = $makeFilter(...$this->mapPrototypeToConditions($filter_prototype));
        }
        return $filters;
    }
}
