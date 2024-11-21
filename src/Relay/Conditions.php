<?php

namespace nostriphant\Transpher\Relay;

readonly class Conditions {

    public function __construct() {
        
    }

    public function __invoke(array $filter_prototype): array {
        $conditions = [];
        foreach ($filter_prototype as $filter_field => $expected_value) {
            $conditions[$filter_field] = match ($filter_field) {
                'authors' => Condition::pubkey($expected_value),
                'ids' => Condition::id($expected_value),
                'kinds' => Condition::kind($expected_value),
                'limit' => Condition::limit($expected_value),
                'since' => Condition::since($expected_value),
                'until' => Condition::until($expected_value),
                default => Condition::$filter_field($expected_value)
            };
        }
        return $conditions;
    }
}
