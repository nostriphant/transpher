<?php

use rikmeijer\Transpher\Nostr\Key;
use rikmeijer\Transpher\Nostr\MessageFactory;
use rikmeijer\Transpher\Relay\InformationDocument;

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
    
    private $messages = [];
    
    public function __construct() {
        
    }
    static function generic_client() : self {
        return new self();
    }
    
    #[\Override]
    public function json(mixed $value) : void {
        $events = [];
        $relay = new \rikmeijer\Transpher\Relay($events);
        
        $relayer = Mockery::mock(\rikmeijer\Transpher\Relay\Sender::class)->allows([
                '__invoke' => true
        ]);
        
        foreach ($relay(\rikmeijer\Transpher\Nostr::encode($value), $relayer) as $response) {
            $this->messages[] = $response;
        }
    }
    
    #[\Override]
    public function receive() : Amp\Websocket\WebsocketMessage {
        return Amp\Websocket\WebsocketMessage::fromText(array_shift($this->messages));
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
    
    $note = MessageFactory::rumor($key_alice(Key::public()), 1, 'Hello world!');
    $signed_note = $note($key_alice);

    $alice->expectNostrOK($signed_note[1]['id']);

    $alice->json($signed_note);
    $alice->start();
});


it('replies NOTICE Invalid message on non-existing filters', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();
    $key_alice = Key::generate();

    $note = MessageFactory::rumor($key_alice(Key::public()), 1, 'Hello world!');
    $alice->sendSignedMessage($note($key_alice));

    $bob->expectNostrNotice('Invalid message');
    $subscription = MessageFactory::subscribe();
    $bob->json($subscription());
    $bob->start();
});

it('replies CLOSED on empty filters', function () {
    $alice = Client::generic_client();
    $bob = Client::generic_client();
    $key_alice = Key::generate();

    $note = MessageFactory::rumor($key_alice(Key::public()), 1, 'Hello world!');
    $alice->sendSignedMessage($note($key_alice));

    $subscription = MessageFactory::subscribe();

    $bob->expectNostrClosed($subscription()[1], 'Subscription filters are empty');
    $request = $subscription();
    $request[] = [];
    $bob->json($request);
    $bob->start();
});
