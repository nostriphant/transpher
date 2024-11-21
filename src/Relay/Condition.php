<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Event;

readonly class Condition {

    public function __construct(private \Closure $test) {
        
    }

    public function __invoke(Event $event): bool {
        return call_user_func($this->test, $event);
    }
}
