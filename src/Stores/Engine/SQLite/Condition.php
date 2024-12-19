<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite;

use nostriphant\Transpher\Stores\Engine\SQLite\Condition\Test;

readonly class Condition {

    public function __invoke(array $conditions): mixed {
        return fn(array $query): array => array_reduce($conditions, fn(array $query, Test $condition) => $condition($query), $query);
    }
}
