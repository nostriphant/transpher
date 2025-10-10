<?php

namespace nostriphant\Transpher;

use nostriphant\Transpher\Amp\Client;
use nostriphant\Transpher\Amp\AwaitSignal;

use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Message;
use nostriphant\NIP17\PrivateDirect;

readonly class Agent {

    private Client $client;
    
    public function __construct(#[\SensitiveParameter] private Key $key, private Bech32 $relay_owner_npub, private string $relay_url) {
        $this->client = new Client(0, $relay_url);
    }
    
    public function __invoke(): AwaitSignal {
        $send = $this->client->start(function (Message $message) {
            
        });

        $gift = PrivateDirect::make($this->key, call_user_func($this->relay_owner_npub), 'Hello, I am your agent! The URL of your relay is ' . $this->relay_url);
        $send(Message::event($gift));

        return $this->client->listen();
    }
}
