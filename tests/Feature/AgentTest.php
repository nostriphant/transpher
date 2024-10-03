<?php

use Transpher\Nostr\Relay\Agent;
use Transpher\Key;
use Transpher\Nostr\Message;

describe('agent', function () : void {
    it('starts relay and sends private direct messsage to relay owner', function () : void {
        $relay = \Transpher\Nostr\Relay::boot(8085, []);
        
        $agent_key = Key::generate();
        $alice_key = Key::generate();
        $agent = Agent::boot(8084, [
            'RELAY_OWNER_NPUB' => $alice_key(Key::public(\Transpher\Nostr\Key\Format::BECH32)), 
            'AGENT_NSEC' => $agent_key(Key::private(\Transpher\Nostr\Key\Format::BECH32)),
            'RELAY_URL' => 'ws://127.0.0.1:8085'
        ]);
        
        $alice = \TranspherTests\Client::client(8085);
        $subscription = Message::subscribe();
        $request = Message::filter($subscription, tags: [['#p' => [$alice_key(Key::public())]]])();
        $alice->expectNostrPrivateDirectMessage($subscription()[1], $alice_key, 'Hello, I am your agent! The URL of your relay is ws://127.0.0.1:8085');
        $alice->json($request);
        expect($request[2])->toBeArray();
        expect($request[2][0])->toBeArray();
        expect($request[2][0]['#p'])->toContain($alice_key(Key::public()));

        $alice->start();

        $agent();
        $relay();
    });
    it('sends an information document (NIP-11), when on a HTTP request', function() {
        $relay = \Transpher\Nostr\Relay::boot(8087, []);
        $owner_key = Key::generate();
        $agent_key = Key::generate();
        $agent = Agent::boot(8086, [
            'AGENT_NSEC' => $agent_key(Key::private(\Transpher\Nostr\Key\Format::BECH32)),
            'RELAY_URL' => 'ws://127.0.0.1:8085',
            'RELAY_OWNER_NPUB' => $owner_key(Key::public(\Transpher\Nostr\Key\Format::BECH32)), 
            'RELAY_NAME' => 'Really relay',
            'RELAY_DESCRIPTION' => 'This is my dev relay',
            'RELAY_CONTACT' => 'nostr@rikmeijer.nl'
        ]);


        $curl = curl_init('http://localhost:8086');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept: application/nostr+json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $responseText = curl_exec($curl);
        expect($responseText)->not()->toBeFalse('['. curl_errno($curl).'] ' . curl_error($curl));
        expect($responseText)->not()->toContain('<b>Warning</b>');
        $response = \Transpher\Nostr::decode($responseText);

        expect($response)->not()->toBeNull();
        expect($response)->toBe([
             "name" => 'Really relay',
             "description" => 'This is my dev relay',
             "pubkey" => $owner_key(Key::public(\Transpher\Nostr\Key\Format::HEXIDECIMAL)),
             "contact" => "nostr@rikmeijer.nl",
             "supported_nips" => [1, 11],
             "software" => 'Transpher',
             "version" => 'dev'
        ]);

        $agent();
        $relay();
    });
});
