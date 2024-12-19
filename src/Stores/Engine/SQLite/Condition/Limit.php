<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

class Limit {

    private int $hits = 0;

    public function __construct(readonly private mixed $expected_value) {
        
    }

    
    public function __invoke(array $query): array {
        $query['limit'] = $this->expected_value;
        return $query;
    }
}
