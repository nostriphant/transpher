<?php

namespace rikmeijer\Transpher\Relay\Incoming;

readonly class Context {

    public function __construct(public \rikmeijer\Transpher\Relay\Store $events, public \rikmeijer\Transpher\Relay\Sender $relay) {
        
    }
}
