<?php

namespace nostriphant\TranspherTests\Acceptance\WhitelistedAuthorsTest;

use nostriphant\NIP01\Rumor;
use nostriphant\NIP01\Key;
use nostriphant\Client\Client;
use nostriphant\TranspherTests\Listener;
use \Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Amp\Cancellation;

class Alice implements Task {
    private readonly Key $key;
    
    public function __construct(
        private readonly string $ws,
        string $key,
            private string $bob_pubkey
    ) {
        $this->key = Key::fromHex($key);
    }

    public function run(Channel $channel, Cancellation $cancellation): string
    {
        $alice = Client::connectToUrl(fn() => null, $this->ws);
        $alice_listener = new Listener('alice-8088', $this->key);
        $alice(function(callable $send, callable $subscribe) use ($alice_listener) {
            $subscription = $subscribe(authors: [$this->bob_pubkey]);
            Listener::expectSubscription($alice_listener, $subscription, 'Hello!');

            $subscription = $subscribe(...['#p' => [($this->key)(Key::public())]]);
            Listener::expectSubscription($alice_listener, $subscription, 
                    'Hello, I am your agent! The URL of your relay is ' . $this->ws,
                    'Running with public key npub15fs4wgrm7sllg4m0rqd3tljpf5u9a2g6443pzz4fpatnvc9u24qsnd6036');

            Listener::expectOK($alice_listener, $send, (new Rumor(
                            pubkey: ($this->key)(Key::public()),
                                    created_at: time(),
                                    kind: 1,
                                    content: 'Hello!',
                                    tags: []
                            ))($this->key));
        });
        
        expect($alice_listener->expected_messages)->toBeEmpty();

        return 'done';
    }
}