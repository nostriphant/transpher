<?php

namespace nostriphant\Transpher\Relay;

readonly class Conditions {

    public function __construct() {
        
    }

    public function __invoke(array $filter_prototype): array {
        $conditions = [];
        foreach ($filter_prototype as $filter_field => $expected_value) {
            $conditions[$filter_field] = match ($filter_field) {
                'authors' => Condition::scalar('pubkey', $expected_value),
                'ids' => Condition::scalar('id', $expected_value),
                'kinds' => Condition::scalar('kind', $expected_value),
                'limit' => Condition::limit($expected_value),
                'since' => Condition::since('created_at', $expected_value),
                'until' => Condition::until('created_at', $expected_value),
                default => Condition::tag(ltrim($filter_field, '#'), $expected_value)
            };
        }
        return $conditions;
    }
}
