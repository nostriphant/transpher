<?php

namespace nostriphant\Transpher\Relay\Condition;

use nostriphant\NIP01\Event;
use function Functional\some;

readonly class Tag implements Test {

    public function __construct(private string $event_tag_identifier, private mixed $expected_value) {
        
    }

    #[\Override]
    public function __invoke(Event $event): bool {
        return is_array($this->expected_value) === false || some($event->tags, fn(array $event_tag) => $event_tag[0] === $this->event_tag_identifier && in_array($event_tag[1], $this->expected_value));
    }
}
