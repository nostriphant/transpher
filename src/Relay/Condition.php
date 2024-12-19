<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Relay\Condition\Test;

readonly class Condition {

    public function __construct(private Event $event) {
        
    }

    public function __invoke(array $conditions): bool {
        return array_reduce($conditions, fn(bool $result, Test $condition) => $result && $condition($this->event), true);
    }

    static function makeConditions(Conditions $conditionsFactory): callable {
        $conditions = $conditionsFactory(new ConditionFactory(Test::class));
        return fn(Event $event): bool => array_reduce(array_map(new self($event), $conditions), fn(bool $result, bool $filter) => $result || $filter, false);
    }
}
