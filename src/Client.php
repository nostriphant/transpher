<?php

namespace nostriphant\Transpher;

use function Amp\Websocket\Client\connect;
use nostriphant\NIP01\Message;

class Client {

    private bool $listening = false;
    private \Amp\Websocket\Client\WebsocketConnection $connection;
    private \Amp\Cancellation $cancellation;

    public function __construct(int $timeout, readonly public string $url) {
        $this->cancellation = $timeout > 0 ? new \Amp\TimeoutCancellation($timeout) : new \Amp\NullCancellation();
        $this->connection = connect($this->url);
    }

    public function send(string $text): void {
        $this->connection->sendText($text);
    }

    public function start(callable $callback): void {
        $this->listening = true;
        while ($this->listening && ($message = $this->connection->receive($this->cancellation))) {
            ($callback)(fn() => $this->listening = false, Message::decode($message->buffer()));
        }
    }

    public function stop(): void {
        $this->listening = false;
        $this->connection->close();
    }
    
}
