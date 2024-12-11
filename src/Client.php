<?php

namespace nostriphant\Transpher;

use function Amp\Websocket\Client\connect;
use nostriphant\NIP01\Message;

class Client {

    private \Amp\Websocket\Client\WebsocketConnection $connection;

    public function __construct(int $timeout, readonly public string $url) {
        $cancellation = $timeout > 0 ? new \Amp\TimeoutCancellation($timeout) : new \Amp\NullCancellation();
        $this->connection = connect($this->url, $cancellation);
    }

    public function start(callable $callback): Relay\Sender {
        \Amp\async(function () use ($callback) {
            foreach ($this->connection as $message) {
                $callback(Message::decode($message->buffer()));
            }
        });
        return SendNostr::send($this->connection);
    }

    public function stop(): void {
        $this->connection->close();
    }
    
}
