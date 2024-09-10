<?php

namespace Transpher\WebSocket;

use Transpher\Nostr;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;
use WebSocket\Middleware\PingInterval;


/**
 * Description of Client
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Client {
    public function __construct(private \WebSocket\Client $client) {
        $this->client
            ->addMiddleware(new CloseHandler())
            ->addMiddleware(new PingResponder())
            ->addMiddleware(new PingInterval(interval: 30));
    }
    
    public function json(mixed $json) {
        $this->client->text(Nostr::encode($json));
    }
    public function onJson(callable $callback) {
        Nostr::onJson($this->client, $callback);
    }
    
    public function start(): void {
        $this->client->start();
    }

    public function connect() : bool {
        $this->client->connect();
        return $this->client->isConnected();
    }
}
