<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

use nostriphant\NIP01\Event;

interface Test {

    public function __invoke(array $query): array;
}
