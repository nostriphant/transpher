<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Message;

readonly class Authenticated implements Context {

    public function __construct(
            private Context $context
    ) {
        
    }

    #[\Override]
    public function __invoke(Sender $client): Incoming {
        $unauthenticated_incoming = call_user_func($this->context, $client);
        $client(Message::auth(bin2hex(random_bytes(32))));
        return Incoming::withType($unauthenticated_incoming, new Incoming\Auth(Incoming\Auth\Limits::fromEnv()));
    }
}
