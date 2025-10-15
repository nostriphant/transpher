<?php

namespace nostriphant\Transpher\Nostr;

use nostriphant\Transpher\Nostr\Transmission;
use nostriphant\NIP01\Message;
use nostriphant\Functional\Await;

readonly class Client {
    
    public function __construct(private string $relay_url, private \Closure $response_callback) {
    }
    
    public function __invoke(callable $bootstrap_callback): callable {
        $connection = \Amp\Websocket\Client\connect($this->relay_url, new \Amp\NullCancellation());
        
        \Amp\async(function() use ($connection) {
            foreach ($connection as $message) {
                ($this->response_callback)(Message::decode($message->buffer()));
            }
        });
        $bootstrap_callback(new class($connection) implements Transmission {
            public function __construct(private \Amp\Websocket\Client\WebsocketConnection $connection) {

            }

            #[\Override]
            public function __invoke(Message $message): bool {
                $this->connection->sendText($message);
                return true;
            }

        });
        return fn(callable $shutdown_callback) => (new Await(fn() => \Amp\trapSignal([SIGINT, SIGTERM])))(function(int $signal) use ($shutdown_callback, $connection) {
            $shutdown_callback($signal);
            $connection->close();
        });
    }
}
