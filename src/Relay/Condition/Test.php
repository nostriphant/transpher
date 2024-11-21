<?php

namespace nostriphant\Transpher\Relay\Condition;

use nostriphant\NIP01\Event;

interface Test {

    public function __invoke(Event $event): bool;
}
