<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

readonly class Until implements Test {

    public function __construct(private mixed $expected_value) {
        
    }

    #[\Override]
    public function __invoke(array $query): array {
        if (is_int($this->expected_value) === false) {
            return $query;
        }
        $query['where'][] = ["event.created_at <= ?", $this->expected_value];
        return $query;
    }
}
