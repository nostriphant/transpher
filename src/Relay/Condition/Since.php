<?php

namespace nostriphant\Transpher\Relay\Condition;

use nostriphant\NIP01\Event;

readonly class Since {

    public function __construct(private int $expected_value) {
        
    }

    
    public function __invoke(Event $event): bool {
        return $event->created_at >= $this->expected_value;
    }
}
