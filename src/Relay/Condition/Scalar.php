<?php

namespace nostriphant\Transpher\Relay\Condition;

use nostriphant\NIP01\Event;

readonly class Scalar {

    public function __construct(private string $event_field, private mixed $expected_value) {
        
    }

    
    public function __invoke(Event $event): bool {
        return is_array($this->expected_value) === false || in_array($event->{$this->event_field}, $this->expected_value);
    }
}
