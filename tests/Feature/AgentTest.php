<?php

use Transpher\Nostr\Relay\Agent;
use Transpher\Key;
use Transpher\Nostr\Message;

describe('agent', function () : void {
    it('starts relay and sends private direct messsage to relay owner', function () : void {
        $relay = \Transpher\Nostr\Relay::boot('127.0.0.1:8085', []);
        
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
});
