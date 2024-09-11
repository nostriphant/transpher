<?php

use Transpher\Nostr\Relay\Agent;

$main_key = \Transpher\Key::generate();
$main_agent;
beforeAll(function() use (&$main_agent, $main_key) {
    Agent::boot(8084, $main_key(), [], function(callable $agent) use (&$main_agent) {
        $main_agent = $agent;
    });
});
afterAll(function() use (&$main_agent) {
    $status = $main_agent();
    expect($status)->toBeArray();
    expect($status['running'])->toBeFalse();
});

describe('agent', function() use ($main_key)  {
     
    $alice = \TranspherTests\Client::client(8084);
    $subscription = Transpher\Message::subscribe();
   
    it('starts relay and seeks connection with client', function () use ($alice, $subscription, $main_key) {
        
        $request = Transpher\Message::filter($subscription, tags: ['#p' => [$main_key()]])();

        $alice->expectNostrPrivateDirectMessage($subscription()[1], 'Hello, I am Agent!');
        $alice->json($request);
        $alice->start();
    });
    
    
});