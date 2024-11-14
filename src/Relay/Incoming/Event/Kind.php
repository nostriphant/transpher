<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Alternate;

interface Kind {

    public function __construct(\nostriphant\Transpher\Relay\Store $store, \nostriphant\Transpher\Files $files);

    static function validate(Event $event): Alternate;

    public function __invoke(Event $event): void;
}
