<?php

namespace nostriphant\Transpher;

use nostriphant\Transpher\Amp\Client;
use nostriphant\Transpher\Amp\AwaitSignal;

use nostriphant\NIP01\Message;

readonly class Agent {

    private Client $client;
    
    public function __construct(private string $relay_url) {
        $this->client = new Client(0, $relay_url);
    }
    
    public function __invoke(callable $logic): AwaitSignal {
        $logic($this->client->start(function (Message $message) {
            
        }));
        return $this->client->listen();
    }
}
