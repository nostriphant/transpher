<?php

namespace nostriphant\Transpher\Relay\Incoming\Event\Accepted;

use nostriphant\NIP01\Event;
use nostriphant\NIP01\Message;

class Undefined {

    public function __construct() {
        
    }

    public function __invoke(Event $event) {
        yield Message::notice('Undefined event kind ' . $event->kind);
    }
}
