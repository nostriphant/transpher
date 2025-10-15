<?php

namespace nostriphant\Transpher\Amp;

use function Amp\Websocket\Client\connect;
use nostriphant\Transpher\Nostr\Transmission;
use nostriphant\NIP01\Message;

class Client {

    private \Amp\Websocket\Client\WebsocketConnection $connection;

    public function __construct(int $timeout, readonly public string $url) {
        $cancellation = $timeout > 0 ? new \Amp\TimeoutCancellation($timeout) : new \Amp\NullCancellation();
        $this->connection = connect($this->url, $cancellation);
    }

    public function start(callable $response_callback): Transmission {
        \Amp\async(function () use ($response_callback) {
            foreach ($this->connection as $message) {
                $response_callback(Message::decode($message->buffer()));
            }
        });
        return new class($this->connection) implements Transmission {
            public function __construct(private \Amp\Websocket\Client\WebsocketConnection $connection) {

            }

            #[\Override]
            public function __invoke(mixed $json): bool {
                if ($json instanceof Message) {
                    $text = $json;
                } else {
                    $text = Nostr::encode($json);
                }
                $this->connection->sendText($text);
                return true;
            }

        };
    }

    public function listen(callable $shutdown_callback): void {
        (new Await(fn() => \Amp\trapSignal([SIGINT, SIGTERM])))(function(int $signal) use ($shutdown_callback) {
            $shutdown_callback($signal);
            $this->connection->close();
        });
    }
    
}
