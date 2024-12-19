<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

use function Functional\some;

readonly class Tag {

    public function __construct(private string $tag, private mixed $expected_value) {
        
    }

    
    public function __invoke(array $query): array {
        if (is_array($this->expected_value) === false) {
            return $query;
        }

        $positionals = array_fill(0, count($this->expected_value), '?');
        $query['where'][] = array_merge(["tag.name = ? AND tag_value.value IN (" . implode(', ', $positionals) . ")", $this->tag], $this->expected_value);
        return $query;
    }
}
