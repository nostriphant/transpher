<?php

namespace nostriphant\Transpher\Relay\Condition;

use nostriphant\NIP01\Event;

readonly class Since {

    public function __construct(private mixed $expected_value) {
        
    }

    
    public function __invoke(Event $event): bool {
        return is_int($this->expected_value) === false || $event->created_at >= $this->expected_value;
    }
}
