<?php

use nostriphant\Transpher\SendNostr;

it('can provide a Nostr-reply callable', function() {
    $client = Mockery::mock(\Amp\Websocket\WebsocketClient::class);
    $client->shouldReceive('sendText')->with('["EVENT",{"id":"12345"}]');
    
    $logger = Mockery::mock(\Monolog\Logger::class);
    $logger->shouldReceive('debug')->with('Reply message ["EVENT",{"id":"12345"}]');

    $replier = SendNostr::reply($client, $logger);
   
    expect($replier(['EVENT', ['id' => '12345']]))->toBeTrue();
});

it('can provide a Nostr-relay callable', function() {
    $client = Mockery::mock(\Amp\Websocket\WebsocketClient::class);
    $client->shouldReceive('sendText')->with('["EVENT",{"id":"12345"}]');
    
    $logger = Mockery::mock(\Monolog\Logger::class);
    $logger->shouldReceive('debug')->with('Relay message ["EVENT",{"id":"12345"}]');

    $replier = SendNostr::relay($client, $logger);
   
    expect($replier(['EVENT', ['id' => '12345']]))->toBeTrue();
});