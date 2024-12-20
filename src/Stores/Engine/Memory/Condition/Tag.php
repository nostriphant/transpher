<?php

namespace nostriphant\Transpher\Stores\Engine\Memory\Condition;

use nostriphant\NIP01\Event;

readonly class Tag {

    public function __construct(private string $tag, private array $expected_value) {
        
    }

    
    public function __invoke(Event $event): bool {
        return array_reduce($event->tags, fn(bool $carry, array $event_tag) => $carry || $event_tag[0] === $this->tag && in_array($event_tag[1], $this->expected_value), false);
    }
}
