<?php

namespace nostriphant\Transpher\SQLite\Condition;

use function Functional\some;

readonly class Tag implements Test {

    public function __construct(private string $tag, private mixed $expected_value) {
        
    }

    #[\Override]
    public function __invoke(array $query): array {
        if (is_array($this->expected_value) === false) {
            return $query;
        }

        $positionals = array_fill(0, count($this->expected_value), '?');
        $query['where'][] = array_merge(["event.id IN (SELECT event_id FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE name = ? AND tag_value.value IN (" . implode(', ', $positionals) . "))", $this->tag], $this->expected_value);
        return $query;
    }
}
