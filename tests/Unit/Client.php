<?php

namespace rikmeijer\TranspherTests\Unit;

use rikmeijer\Transpher\Nostr\Filters;
use rikmeijer\Transpher\Nostr\Message\Factory;
use Amp\Websocket\WebsocketMessage;
use function \Functional\select,
             \Functional\map,
             \Functional\partial_left;

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

                private array $events = [];

                #[\Override]
                public function __invoke(Filters $subscription): callable {
                    return fn(string $subscriptionId) => map(select($this->events, $subscription), partial_left([Factory::class, 'requestedEvent'], $subscriptionId));
                }

                #[\Override]
                public function offsetExists(mixed $offset): bool {
                    return isset($this->events[$offset]);
                }

                #[\Override]
                public function offsetGet(mixed $offset): mixed {
                    return $this->events[$offset];
                }

                #[\Override]
                public function offsetSet(mixed $offset, mixed $value): void {
                    if (isset($offset)) {
                        $this->events[$offset] = $value;
                    } else {
                        $this->events[] = $value;
                    }
                }

                #[\Override]
                public function offsetUnset(mixed $offset): void {
                    unset($this->events[$offset]);
                }
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
