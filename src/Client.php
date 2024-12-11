<?php

namespace nostriphant\Transpher;

use function Amp\Websocket\Client\connect;
use nostriphant\NIP01\Message;

class Client {

    private bool $listening = false;
    private \Amp\Websocket\Client\WebsocketConnection $connection;
    private \Amp\Pipeline\Pipeline $pipeline;

    public function __construct(int $timeout, readonly public string $url) {
        $cancellation = $timeout > 0 ? new \Amp\TimeoutCancellation($timeout) : new \Amp\NullCancellation();
        $this->connection = connect($this->url, $cancellation);
        $this->pipeline = \Amp\Pipeline\Pipeline::fromIterable($this->connection)->unordered();
    }

    public function send(string $text): void {
        $this->connection->sendText($text);
    }

    public function start(callable $callback): void {
        $this->pipeline->tap(fn($message) => $callback(fn() => $this->listening = false, Message::decode($message->buffer())));
    }

    public function stop(): void {
        $this->listening = false;
        $this->connection->close();
    }
    
}
