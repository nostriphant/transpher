<?php

namespace rikmeijer\TranspherTests\Unit;

use Amp\Websocket\WebsocketMessage;
use rikmeijer\Transpher\Relay\Incoming\Context;

class Client extends \rikmeijer\TranspherTests\Client {

    static \rikmeijer\Transpher\Relay $generic_relay;
    private $messages = [];

    public function __construct(private \rikmeijer\Transpher\Relay $relay) {
        
    }

    static function persistent_client(string $store): self {
        return new self(new \rikmeijer\Transpher\Relay(
                        new \rikmeijer\Transpher\Directory($store),
                        \Mockery::spy(\Psr\Log\LoggerInterface::class)
                ));
    }

    static function generic_client(): self {
        if (isset(self::$generic_relay) === false) {
            $events = new class([]) implements \rikmeijer\Transpher\Relay\Store {

                use \rikmeijer\Transpher\Nostr\EventsStore;
            };
            self::$generic_relay = new \rikmeijer\Transpher\Relay(
                    $events,
                    \Mockery::spy(\Psr\Log\LoggerInterface::class)
            );
        }
        return new self(self::$generic_relay);
    }

    public function relay(mixed $json) {
        $this->messages[] = $json;
    }

    #[\Override]
    public function privateDirectMessage(\rikmeijer\Transpher\Nostr\Key $sender, string $recipient_npub, string $message) {
        $note = \rikmeijer\Transpher\Nostr\Message\Factory::privateDirect($sender, \rikmeijer\Transpher\Nostr\Key::convertBech32ToHex($recipient_npub), $message);
        $this->send($note);
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

        $websocket_client = \Mockery::mock(\Amp\Websocket\WebsocketClient::class);
        $websocket_client->allows([
            'getId' => 10203,
            'onClose' => null,
            'getIterator' => new \ArrayIterator([$text])
        ]);
        $websocket_client->shouldReceive('sendText')->andReturnUsing(function (string $text) {
            $this->messages[] = $text;
        });

        $request = new \Amp\Http\Server\Request(\Mockery::mock(\Amp\Http\Server\Driver\Client::class), 'GET', \Mockery::mock(\Psr\Http\Message\UriInterface::class));
        $response = new \Amp\Http\Server\Response();

        $this->relay->handleClient($websocket_client, $request, $response);
    }

    #[\Override]
    public function receive(): ?WebsocketMessage {
        return !empty($this->messages) ? WebsocketMessage::fromText(array_shift($this->messages)) : null;
    }
}
