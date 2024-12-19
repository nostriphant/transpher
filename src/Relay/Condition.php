<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Relay\Condition\Test;

readonly class Condition {

    public function __invoke(array $conditions) {
        return fn(Event $event): bool => array_reduce($conditions, fn(bool $result, Test $condition) => $result && $condition($event), true);
    }

    static function makeConditions(Conditions $conditionsFactory): callable {
        $conditions = array_map(
                new self(),
                $conditionsFactory(new ConditionFactory(Test::class))
        );
        return fn(Event $event): bool => array_reduce($conditions, fn(bool $result, callable $filter) => $result || $filter($event), false);
    }
}
