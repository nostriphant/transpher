<?php

namespace nostriphant\Transpher\Amp;

use function Amp\Websocket\Client\connect;
use nostriphant\NIP01\Message;
use nostriphant\Transpher\Nostr\Send;

class Client {

    private \Amp\Websocket\Client\WebsocketConnection $connection;

    public function __construct(int $timeout, readonly public string $url) {
        $cancellation = $timeout > 0 ? new \Amp\TimeoutCancellation($timeout) : new \Amp\NullCancellation();
        $this->connection = connect($this->url, $cancellation);
    }

    public function start(callable $response_callback): \nostriphant\Transpher\Nostr\Transmission {
        \Amp\async(function () use ($response_callback) {
            foreach ($this->connection as $message) {
                $response_callback(Message::decode($message->buffer()));
            }
        });
        return Send::send($this->connection);
    }

    public function listen(callable $shutdown_callback): void {
        (new Await(fn() => \Amp\trapSignal([SIGINT, SIGTERM])))(function(int $signal) use ($shutdown_callback) {
            $shutdown_callback($signal);
            $this->connection->close();
        });
    }
    
}
