<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

readonly class Tag {

    public function __construct(private string $tag, private array $expected_value) {
        
    }

    public function __invoke(): array {
        $positionals = array_fill(0, count($this->expected_value), '?');
        return array_merge(["tag.name = ? AND tag_value.value IN (" . implode(', ', $positionals) . ")", $this->tag], $this->expected_value);
    }
}
