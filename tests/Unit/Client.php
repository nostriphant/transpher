<?php

namespace nostriphant\TranspherTests\Unit;

use Amp\Websocket\WebsocketMessage;
use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Key;

class Client extends \nostriphant\TranspherTests\Client {

    static \nostriphant\Transpher\Relay $generic_relay;
    private $messages = [];

    public function __construct(private \nostriphant\Transpher\Relay $relay) {
        
    }

    static function persistent_client(string $store): self {
        return new self(new \nostriphant\Transpher\Relay(
                        \Pest\incoming(new \nostriphant\Transpher\Stores\Disk($store, [])),
                        \Mockery::spy(\Psr\Log\LoggerInterface::class)
                ));
    }

    static function generic_client(bool $reset = false): self {
        if ($reset || isset(self::$generic_relay) === false) {
            self::$generic_relay = new \nostriphant\Transpher\Relay(
                    \Pest\incoming(),
                    \Mockery::spy(\Psr\Log\LoggerInterface::class)
            );
        }
        return new self(self::$generic_relay);
    }

    public function relay(mixed $json) {
        $this->messages[] = $json;
    }

    #[\Override]
    public function privateDirectMessage(Key $sender, string $recipient_npub, string $message) {
        $note = \nostriphant\Transpher\Nostr\Message\Factory::privateDirect($sender, Bech32::fromNpub($recipient_npub), $message);
        $this->send($note);
    }

    #[\Override]
    public function send(string $text): void {
        $relayer = new class($this) implements \nostriphant\Transpher\Relay\Sender {

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
    public function receive(int $timeout): ?WebsocketMessage {
        return !empty($this->messages) ? WebsocketMessage::fromText(array_shift($this->messages)) : null;
    }
}
