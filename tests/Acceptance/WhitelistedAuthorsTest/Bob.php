<?php

namespace nostriphant\TranspherTests\Acceptance\WhitelistedAuthorsTest;

use nostriphant\NIP01\Key;
use nostriphant\Client\Client;
use nostriphant\TranspherTests\Listener;
use \Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Amp\Cancellation;

class Bob implements Task {
    private readonly Key $key;

    public function __construct(
        private readonly string $ws,
        string $key,
            private \nostriphant\NIP01\Event $message
    ) {
        $this->key = Key::fromHex($key);
    }

    public function run(Channel $channel, Cancellation $cancellation): string
    {
        $bob = Client::connectToUrl(fn() => null, $this->ws);
        $bob_listener = new Listener('bob-8088', $this->key);

        $bob(function(callable $send) use ($bob_listener) { 
            Listener::expectOK($bob_listener, $send, $this->message);
        });
        
        expect($bob_listener->expected_messages)->toBeEmpty();
        
        return 'done';
    }
}