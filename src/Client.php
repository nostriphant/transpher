<?php

namespace nostriphant\Transpher;

use nostriphant\NIP01\Nostr;
use function Amp\Websocket\Client\connect;
use nostriphant\NIP01\Message;

class Client {

    private bool $listening = false;
    private \Amp\Websocket\Client\WebsocketConnection $connection;

    public function __construct(readonly public string $url) {
        $this->connection = connect($this->url);
    }

    public function send(string $text): void {
        $this->connection->sendText($text);
    }

    public function receive(int $timeout): ?\Amp\Websocket\WebsocketMessage {
        return $this->connection->receive($timeout > 0 ? new \Amp\TimeoutCancellation($timeout) : null);
    }

    public function start(int $timeout, callable $callback): void {
        $this->listening = true;
        while ($this->listening && ($message = $this->receive($timeout))) {
            ($callback)(fn() => $this->listening = false, Message::decode($message->buffer()));
        }
    }

    public function stop(): void {
        $this->listening = false;
        $this->connection->close();
    }
    
}
