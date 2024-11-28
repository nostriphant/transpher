<?php

namespace nostriphant\Transpher\Stores\SQLite;

use function Functional\true;

readonly class Filter {

    public array $conditions;

    public function __construct(Condition ...$conditions) {
        $this->conditions = $conditions;
    }

    public function __invoke(array $query): array {
        return array_reduce($this->conditions, function (array $query, callable $subscription_filter) {
            return $subscription_filter($query);
        }, $query);
    }

    static function fromPrototype(Condition ...$conditions) {
        return new self(...$conditions);
    }
}
