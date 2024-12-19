<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite;

use nostriphant\Transpher\Stores\Engine\SQLite\Condition\Test;

readonly class Condition {

    public function __construct() {
        
    }

    public function __invoke(array $conditions): mixed {
        return array_reduce($conditions, fn(array $query, Test $condition) => $condition($query), [
            'where' => [],
            'limit' => null
        ]);
    }
}
