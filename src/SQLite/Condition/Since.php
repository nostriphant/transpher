<?php

namespace nostriphant\Transpher\SQLite\Condition;

use nostriphant\NIP01\Event;

readonly class Since implements Test {

    public function __construct(private string $event_field, private mixed $expected_value) {
        
    }

    #[\Override]
    public function __invoke(array $query): array {
        return is_int($this->expected_value) === false || $event->{$this->event_field} >= $this->expected_value;
    }
}
