<?php

namespace nostriphant\Transpher\Relay\Condition;

use nostriphant\NIP01\Event;

readonly class Since implements Test {

    public function __construct(private string $event_field, private mixed $expected_value) {
        
    }

    #[\Override]
    public function __invoke(Event $event): bool {
        return is_int($this->expected_value) === false || $event->{$this->event_field} >= $this->expected_value;
    }
}
