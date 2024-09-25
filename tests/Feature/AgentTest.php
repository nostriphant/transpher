<?php

use Transpher\Nostr\Relay\Agent;
use Transpher\Key;
use Transpher\Nostr\Message;

describe('agent', function () : void {
    it('starts relay and seeks connection with client', function () : void {
        
        $agent_key = Key::generate();
        $alice_key = Key::generate();
        Agent::boot(8084, [
                'AGENT_OWNER_NPUB' => $alice_key(Key::public(\Transpher\Nostr\Key\Format::BECH32)), 
                'AGENT_NSEC' => $agent_key(Key::private(\Transpher\Nostr\Key\Format::BECH32))
            ], 
            function (callable $agent) use ($alice_key) : void {
                $alice = \TranspherTests\Client::client(8084);
                $subscription = Message::subscribe();
                
                $request = Message::filter($subscription, tags: [['#p' => [$alice_key(Key::public())]]])();
                $alice->expectNostrPrivateDirectMessage($subscription()[1], $alice_key, 'Hello, I am Agent!');
                $alice->json($request);
                $alice->start();
                
                $agent();
            }
        );
    });
});
