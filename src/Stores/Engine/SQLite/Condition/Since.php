<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

readonly class Since {

    public function __construct(private int $expected_value) {
        
    }

    
    public function __invoke(array $where): array {
        $where[] = ["event.created_at >= ?", $this->expected_value];
        return $where;
    }
}
