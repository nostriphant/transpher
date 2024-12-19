<?php

namespace nostriphant\Transpher\Relay\Condition;

use nostriphant\NIP01\Event;
use function Functional\some;

readonly class Tag {

    public function __construct(private string $tag, private array $expected_value) {
        
    }

    
    public function __invoke(Event $event): bool {
        return some($event->tags, fn(array $event_tag) => $event_tag[0] === $this->tag && in_array($event_tag[1], $this->expected_value));
    }
}
