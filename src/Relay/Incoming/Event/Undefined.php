<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Event;

class Undefined {

    public function __construct() {
        
    }

    public function __invoke(Event $event) {
        yield Factory::notice('Undefined event kind ' . $event->kind);
    }
}
