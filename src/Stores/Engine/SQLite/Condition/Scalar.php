<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

readonly class Scalar {

    public function __construct(private string $event_field, private mixed $expected_value) {
        
    }

    
    public function __invoke(array $query): array {
        if (is_array($this->expected_value) === false) {
            return $query;
        }
        $positionals = array_fill(0, count($this->expected_value), '?');
        $query['where'][] = array_merge(["event.{$this->event_field} IN (" . implode(', ', $positionals) . ")"], $this->expected_value);
        return $query;
    }
}
