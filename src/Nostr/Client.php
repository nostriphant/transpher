<?php

namespace nostriphant\Transpher\Nostr;

readonly class Client {
    
    public function __construct(private string $relay_url, private \Closure $response_callback) {
    }
    
    public function __invoke(callable $bootstrap_callback): callable {
        $client = new \nostriphant\Transpher\Amp\Client(0, $this->relay_url);
        $bootstrap_callback($client->start($this->response_callback));
        return fn(callable $shutdown_callback) => $client->listen($shutdown_callback);
    }
}
