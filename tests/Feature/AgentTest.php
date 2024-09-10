<?php

use Transpher\Nostr\Server\Agent;

describe('agent', function()  {
   
    it('starts relay and seeks connection with client', function () {
        $alice = \TranspherTests\Client::client(8084);
        $key = \Transpher\Key::generate();
        
        expect([$alice, 'connect'])->toThrow("Could not open socket to \"tcp://127.0.0.1:8084\": Client could not connect to \"tcp://127.0.0.1:8084\"");
        
        Agent::boot(8084, $key(), [], function(callable $agent) use ($alice, $key) {
            expect($alice->connect())->toBeTrue();

            $status = $agent();
            expect($status)->toBeArray();
            expect($status['running'])->toBeFalse();
        });
    });
    
    
});