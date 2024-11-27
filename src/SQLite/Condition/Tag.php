<?php

namespace nostriphant\Transpher\SQLite\Condition;

use nostriphant\NIP01\Event;
use function Functional\some;

readonly class Tag implements Test {

    public function __construct(private string $tag, private mixed $expected_value) {
        
    }

    #[\Override]
    public function __invoke(array $query): array {
        return is_array($this->expected_value) === false || some($event->tags, fn(array $event_tag) => $event_tag[0] === $this->tag && in_array($event_tag[1], $this->expected_value));
    }
}
