<?php

namespace nostriphant\Transpher\Nostr;

readonly class Client {

    private \nostriphant\Transpher\Amp\Client $client;
    
    public function __construct(string $relay_url, private \Closure $response_callback) {
        $this->client = new \nostriphant\Transpher\Amp\Client(0, $relay_url);
    }
    
    public function __invoke(callable $bootstrap_callback, callable $shutdown_callback): void {
        $bootstrap_callback($this->client->start($this->response_callback));
        $this->client->listen($shutdown_callback);
    }
}
