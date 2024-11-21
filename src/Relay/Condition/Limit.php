<?php

namespace nostriphant\Transpher\Relay\Condition;

use nostriphant\NIP01\Event;

class Limit implements Test {

    private int $hits = 0;

    public function __construct(readonly private mixed $expected_value) {
        
    }

    #[\Override]
    public function __invoke(Event $event): bool {
        $this->hits++;
        return is_int($this->expected_value) === false || $this->expected_value >= $this->hits;
    }
}
