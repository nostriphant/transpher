<?php

namespace nostriphant\Transpher\SQLite\Condition;

readonly class Scalar implements Test {

    public function __construct(private string $event_field, private mixed $expected_value) {
        
    }

    #[\Override]
    public function __invoke(array $query): array {
        if (is_array($this->expected_value) === false) {
            return $query;
        }

        $positionals = array_fill(0, count($this->expected_value), '?');

        $query['where'][] = array_merge(["{$this->event_field} IN (" . implode(', ', $positionals) . ")"], $this->expected_value);
        return $query;
    }
}
