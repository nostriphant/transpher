<?php

namespace nostriphant\Transpher\Stores\SQLite;

readonly class Filter {

    public array $conditions;

    public function __construct(Condition ...$conditions) {
        $this->conditions = $conditions;
    }

    public function __invoke(array $query): array {
        return array_reduce($this->conditions, fn(array $query, Condition $condition) => $condition($query), $query);
    }

    static function fromPrototype(Condition ...$conditions) {
        return new self(...$conditions);
    }
}
