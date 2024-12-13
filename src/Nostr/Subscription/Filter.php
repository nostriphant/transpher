<?php

namespace nostriphant\Transpher\Nostr\Subscription;

use nostriphant\Transpher\Relay\Condition;
use nostriphant\NIP01\Event;

readonly class Filter {

    public array $conditions;

    public function __construct(Condition ...$conditions) {
        $this->conditions = $conditions;
    }

    public function __invoke(Event $event): bool {
        return array_reduce($this->conditions, fn(bool $result, Condition $condition) => $result && $condition($event), true);
    }

    static function fromPrototype(Condition ...$conditions) {
        return new self(...$conditions);
    }
}
