<?php

use nostriphant\Transpher\Nostr\Send;

it('can provide a Nostr-reply callable', function() {
    $client = Mockery::mock(\Amp\Websocket\WebsocketClient::class);
    $client->shouldReceive('sendText')->with('["EVENT",{"id":"12345"}]');
    
    $logger = Mockery::mock(\Monolog\Logger::class);
    $logger->shouldReceive('debug')->with('Reply message ["EVENT",{"id":"12345"}]');

    $replier = Send::reply($client, $logger);
   
    expect($replier(['EVENT', ['id' => '12345']]))->toBeTrue();
});

it('can provide a Nostr-relay callable', function() {
    $client = Mockery::mock(\Amp\Websocket\WebsocketClient::class);
    $client->shouldReceive('sendText')->with('["EVENT",{"id":"12345"}]');
    
    $logger = Mockery::mock(\Monolog\Logger::class);
    $logger->shouldReceive('debug')->with('Relay message ["EVENT",{"id":"12345"}]');

    $replier = Send::relay($client, $logger);
   
    expect($replier(['EVENT', ['id' => '12345']]))->toBeTrue();
});