<?php

namespace nostriphant\Transpher\Amp;

use function Amp\Websocket\Client\connect;
use nostriphant\NIP01\Message;

class Client {

    private \Amp\Websocket\Client\WebsocketConnection $connection;

    public function __construct(int $timeout, readonly public string $url) {
        $cancellation = $timeout > 0 ? new \Amp\TimeoutCancellation($timeout) : new \Amp\NullCancellation();
        $this->connection = connect($this->url, $cancellation);
    }

    public function start(callable $response_callback): \nostriphant\Transpher\Relay\Sender {
        \Amp\async(function () use ($response_callback) {
            foreach ($this->connection as $message) {
                $response_callback(Message::decode($message->buffer()));
            }
        });
        return SendNostr::send($this->connection);
    }

    public function listen(): AwaitSignal {
        return new AwaitSignal(fn() => $this->connection->close());
    }
    
}
