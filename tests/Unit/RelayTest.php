<?php

use rikmeijer\Transpher\Nostr\Key;
use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\Relay\InformationDocument;
use rikmeijer\Transpher\Nostr\Subscription\Filter;
use rikmeijer\Transpher\Nostr\Filters;
use function \Functional\select,
             \Functional\map,
             \Functional\partial_left;

it('generates a NIP11 Relay Information Document', function() {
    
    $name = 'Transpher Relay';
    $description = 'Some interesting description goes here';
    $owner_npub = 'npub1cza3sx7rn389ja5gqkaut0wnf3gg799srg5c6ca7g5gdjaqhecqsg485p4';
    $contact = 'nostr@rikmeijer.nl';
    
    expect(InformationDocument::generate($name, $description, $owner_npub, $contact))->toBe([
        "name" => 'Transpher Relay',
        "description" => 'Some interesting description goes here',
        "pubkey" => 'c0bb181bc39c4e59768805bbc5bdd34c508f14b01a298d63be4510d97417ce01',
        "contact" => 'nostr@rikmeijer.nl',
        "supported_nips" => [1, 11],
        "software" => 'Transpher',
        "version" => 'dev'
    ]);
    
});

class Client extends \rikmeijer\TranspherTests\Client {

    static rikmeijer\Transpher\Relay $generic_relay;
    private $messages = [];

    public function __construct(private rikmeijer\Transpher\Relay $relay) {
        
    }

    static function persistent_client(string $store): self {
        return new self(new \rikmeijer\Transpher\Relay(new \rikmeijer\Transpher\Directory($store)));
    }

    static function generic_client(): self {
        if (isset(self::$generic_relay) === false) {
            $events = new class implements rikmeijer\Transpher\Relay\Store {

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
    public function receive() : Amp\Websocket\WebsocketMessage {
        return Amp\Websocket\WebsocketMessage::fromText(array_shift($this->messages) ?? '');
    }
    
}

it('responds with a NOTICE on null message', function () {
    $alice = Client::generic_client();

    $alice->expectNostrNotice('Invalid message');

    $alice->json(null);
    $alice->start();
});


it('responds with a NOTICE on missing subscription-id with close request', function () {
    $alice = Client::generic_client();

    $alice->expectNostrNotice('Missing subscription ID');

    $alice->json(['CLOSE']);
    $alice->start();
});

it('responds with a NOTICE on unsupported message types', function () {
    $alice = Client::generic_client();

    $alice->expectNostrNotice('Message type UNKNOWN not supported');

    $alice->json(['UNKNOWN', uniqid()]);
    $alice->start();
});


it('responds with OK on simple events', function () {
    $alice = Client::generic_client();
    $key_alice = Key::generate();
    
    $note = Factory::event($key_alice, 1, 'Hello world!');

    $alice->expectNostrOK($note()[1]['id']);

    $alice->send($note);
    $alice->start();
});


it('replies NOTICE Invalid message on non-existing filters', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();
    $key_alice = Key::generate();

    $alice->sendSignedMessage(Factory::event($key_alice, 1, 'Hello world!'));

    $bob->expectNostrNotice('Invalid message');
    $subscription = Factory::subscribe();
    $bob->json($subscription());
    $bob->start();
});

it('replies CLOSED on empty filters', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();
    $key_alice = Key::generate();

    $alice->sendSignedMessage(Factory::event($key_alice, 1, 'Hello world!'));

    $subscription = Factory::subscribe();

    $bob->expectNostrClosed($subscription()[1], 'Subscription filters are empty');
    $request = $subscription();
    $request[] = [];
    $bob->json($request);
    $bob->start();
});


it('sends events to all clients subscribed on event id', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();

    $alice_key = Key::generate();
    $alice->sendSignedMessage(Factory::event($alice_key, 1, 'Hello worlda!'));

    $key_charlie = Key::generate();
    $note2 = Factory::event($key_charlie, 1, 'Hello worldi!');
    $alice->sendSignedMessage($note2);

    $subscription = Factory::subscribe(
            new Filter(ids: [$note2()[1]['id']])
    );

    $bob->expectNostrEvent($subscription()[1], 'Hello worldi!');
    $bob->expectNostrEose($subscription()[1]);
    $bob->json($subscription());
    $bob->start();
});


it('sends events to all clients subscribed on author (pubkey)', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();

    $alice_key = Key::generate();
    $alice->sendSignedMessage(Factory::event($alice_key, 1, 'Hello world!'));
    $subscription = Factory::subscribe(
            new Filter(authors: [$alice_key(Key::public())])
    );

    $bob->expectNostrEvent($subscription()[1], 'Hello world!');
    $bob->expectNostrEose($subscription()[1]);

    $bob->json($subscription());
    $bob->start();
});

it('sends events to Charly who uses two filters in their subscription', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();
    $charlie = Client::generic_client();

    $alice_key = Key::generate();
    $alice->sendSignedMessage(Factory::event($alice_key, 1, 'Hello world, from Alice!'));

    $bob_key = Key::generate();
    $alice->sendSignedMessage(Factory::event($bob_key, 1, 'Hello world, from Bob!'));

    $subscription = Factory::subscribe(
            new Filter(authors: [$alice_key(Key::public())]),
            new Filter(authors: [$bob_key(Key::public())])
    );
    $charlie->expectNostrEvent($subscription()[1], 'Hello world, from Alice!');
    $charlie->expectNostrEvent($subscription()[1], 'Hello world, from Bob!');
    $charlie->expectNostrEose($subscription()[1]);

    $charlie->json($subscription());
    $charlie->start();
});

it('sends events to all clients subscribed on p-tag', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();
    $alice_key = Key::generate();

    $alice->sendSignedMessage(Factory::event($alice_key, 1, 'Hello world!', ['p', 'randomPTag']));
    $subscription = Factory::subscribe(
            new Filter(tags: ['#p' => ['randomPTag']])
    );

    $bob->expectNostrEvent($subscription()[1], 'Hello world!');
    $bob->expectNostrEose($subscription()[1]);

    $bob->json($subscription());
    $bob->start();
});

it('closes subscription and stop sending events to subscribers', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();
    $alice_key = Key::generate();

    $alice->sendSignedMessage(Factory::event($alice_key, 1, 'Hello world!'));

    $subscription = Factory::subscribe(
            new Filter(authors: [$alice_key(Key::public())])
    );
    $bob->expectNostrEvent($subscription()[1], 'Hello world!');
    $bob->expectNostrEose($subscription()[1]);

    $bob->json($subscription());
    $bob->start();

    $bob->expectNostrClosed($subscription()[1], '');

    $request = Factory::close($subscription()[1]);
    $bob->send($request);
    $bob->start();
});


it('sends events to all clients subscribed on kind', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();
    $alice_key = Key::generate();

    $alice->sendSignedMessage(Factory::event($alice_key, 3, 'Hello world!'));

    $subscription = Factory::subscribe(
            new Filter(kinds: [3])
    );

    $bob->expectNostrEvent($subscription()[1], 'Hello world!');
    $bob->expectNostrEose($subscription()[1]);

    $bob->json($subscription());
    $bob->start();
});

it('relays events to Bob, sent after they subscribed on Alices messages', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();
    $alice_key = Key::generate();

    $subscription = Factory::subscribe(
            new Filter(authors: [$alice_key(Key::public())])
    );

    $bob->expectNostrEose($subscription()[1]);
    $bob->json($subscription());
    $bob->start();

    $alice->sendSignedMessage(Factory::event($alice_key, 1, 'Relayable Hello worlda!'));

    $key_charlie = Key::generate();
    $alice->sendSignedMessage(Factory::event($key_charlie, 1, 'Hello worldi!'));

    $bob->expectNostrEvent($subscription()[1], 'Relayable Hello worlda!');
    $bob->expectNostrEose($subscription()[1]);
    $bob->start();
});

it('sends events to all clients subscribed on author (pubkey), even after restarting the server', function () {
    $transpher_store = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid();
    mkdir($transpher_store);

    $alice = Client::persistent_client($transpher_store);

    $alice_key = Key::generate();
    $alice->sendSignedMessage(Factory::event($alice_key, 1, 'Hello wirld!'));

    $bob = Client::persistent_client($transpher_store);
    $subscription = Factory::subscribe(
            new Filter(authors: [$alice_key(Key::public())])
    );

    $bob->expectNostrEvent($subscription()[1], 'Hello wirld!');
    $bob->expectNostrEose($subscription()[1]);

    $bob->json($subscription());
    $bob->start();
});
