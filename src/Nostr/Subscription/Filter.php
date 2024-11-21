<?php

namespace nostriphant\Transpher\Nostr\Subscription;

use nostriphant\Transpher\Relay\Condition;
use function Functional\true;
use nostriphant\NIP01\Event;

readonly class Filter {

    public array $conditions;

    public function __construct(Condition ...$conditions) {
        $this->conditions = $conditions;
    }

    public function __invoke(Event $event): bool {
        return true(array_map(fn(callable $subscription_filter) => $subscription_filter($event), $this->conditions));
    }

    static function fromPrototype(Condition ...$conditions) {
        return new self(...$conditions);
    }
}
