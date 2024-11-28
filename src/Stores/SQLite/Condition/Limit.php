<?php

namespace nostriphant\Transpher\Stores\SQLite\Condition;

class Limit implements Test {

    private int $hits = 0;

    public function __construct(readonly private mixed $expected_value) {
        
    }

    #[\Override]
    public function __invoke(array $query): array {
        $query['limit'] = $this->expected_value;
        return $query;
    }
}
