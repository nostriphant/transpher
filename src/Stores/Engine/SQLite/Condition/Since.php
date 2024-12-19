<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

readonly class Since {

    public function __construct(private mixed $expected_value) {
        
    }

    
    public function __invoke(array $where): array {
        if (is_int($this->expected_value) === false) {
            return $where;
        }
        $where[] = ["event.created_at >= ?", $this->expected_value];
        return $where;
    }
}
