<?php

namespace rikmeijer\TranspherTests\Unit;

use Amp\Websocket\WebsocketMessage;

class Client extends \rikmeijer\TranspherTests\Client {

    static \rikmeijer\Transpher\Relay $generic_relay;
    private $messages = [];

    public function __construct(private \rikmeijer\Transpher\Relay $relay) {
        
    }

    static function persistent_client(string $store): self {
        return new self(new \rikmeijer\Transpher\Relay(new \rikmeijer\Transpher\Directory($store)));
    }

    static function generic_client(): self {
        if (isset(self::$generic_relay) === false) {
            $events = new class implements \rikmeijer\Transpher\Relay\Store {
                use \rikmeijer\Transpher\Nostr\EventsStore;
            };
            self::$generic_relay = new \rikmeijer\Transpher\Relay($events);
        }
        return new self(self::$generic_relay);
    }

    public function relay(mixed $json) {
        $this->messages[] = $json;
    }

    #[\Override]
    public function send(string $text): void {
        $relayer = new class($this) implements \rikmeijer\Transpher\Relay\Sender {

            public function __construct(private Client $client) {

            }

            #[\Override]
            public function __invoke(mixed $json): bool {
                $this->client->relay($json);
                return true;
            }
        };

        foreach (($this->relay)($text, $relayer) as $response) {
            $this->messages[] = $response;
        }
    }

    #[\Override]
    public function receive(): WebsocketMessage {
        return WebsocketMessage::fromText(array_shift($this->messages) ?? '');
    }
}
