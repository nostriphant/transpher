<?php

namespace nostriphant\Transpher\Relay\Incoming\Event\Accepted\Regular;

use nostriphant\NIP01\Event;
use nostriphant\FunctionalAlternate\Alternate;

interface Kind {

    public function __construct(\nostriphant\Transpher\Relay\Store $store, \nostriphant\Transpher\Files $files);

    static function validate(Event $event): Alternate;

    public function __invoke(Event $event): void;
}
