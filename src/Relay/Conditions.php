<?php

namespace nostriphant\Transpher\Relay;

readonly class Conditions {

    public function __construct() {
        
    }

    public function __invoke(array $filter_prototype): array {
        $conditions = [];
        foreach ($filter_prototype as $filter_field => $expected_value) {
            $conditions[$filter_field] = (match ($filter_field) {
                'authors' => Condition::scalar('pubkey'),
                        'ids' => Condition::scalar('id'),
                        'kinds' => Condition::scalar('kind'),
                        'limit' => Condition::limit(),
                        'since' => Condition::since('created_at'),
                        'until' => Condition::until('created_at'),
                        default => Condition::tag(ltrim($filter_field, '#'))
                    })($expected_value);
        }
        return $conditions;
    }
}
