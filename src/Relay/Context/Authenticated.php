<?php

namespace nostriphant\Transpher\Relay\Context;

use nostriphant\NIP01\Message;
use nostriphant\Transpher\Relay\Sender;

readonly class Authenticated implements \nostriphant\Transpher\Relay\Context {

    public function __construct(
            private \nostriphant\Transpher\Relay\Context $context
    ) {
        
    }

    #[\Override]
    public function __invoke(Sender $client): \nostriphant\Transpher\Relay\Incoming {
        $unauthenticated_incoming = call_user_func($this->context, $client);
        $client(Message::auth(bin2hex(random_bytes(32))));
        return \nostriphant\Transpher\Relay\Incoming::withType($unauthenticated_incoming, new \nostriphant\Transpher\Relay\Incoming\Auth(\nostriphant\Transpher\Relay\Incoming\Auth\Limits::fromEnv()));
    }
}
