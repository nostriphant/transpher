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
        $this->client->onText(function(...$args) use ($callback) {
            $message = array_pop($args);
            $callback($args[0], $args[1], json_decode($message->getContent(), true));
        });
    }

    public function connect() : bool {
        $this->client->connect();
        return $this->client->isConnected();
    }
    
    public function __call(string $name, array $arguments): mixed {
        return $this->client->{$name}(...$arguments);
    }
}
