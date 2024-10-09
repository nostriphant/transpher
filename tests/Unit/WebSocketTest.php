<?php

it('can provide a Nostr-reply callable', function() {
    $client = Mockery::mock(\Amp\Websocket\WebsocketClient::class);
    $client->shouldReceive('sendText')->with('["EVENT",{"id":"12345"}]');
    
    $logger = Mockery::mock(\Monolog\Logger::class);
    $logger->shouldReceive('info')->with('Reply message ["EVENT",{"id":"12345"}]');
    
    $replier = rikmeijer\Transpher\WebSocket\SendNostr::reply($client, $logger);
   
    expect($replier(['EVENT', ['id' => '12345']]))->toBeTrue();
});

it('can provide a Nostr-relay callable', function() {
    $client = Mockery::mock(\Amp\Websocket\WebsocketClient::class);
    $client->shouldReceive('sendText')->with('["EVENT",{"id":"12345"}]');
    
    $logger = Mockery::mock(\Monolog\Logger::class);
    $logger->shouldReceive('info')->with('Relay message ["EVENT",{"id":"12345"}]');
    
    $replier = rikmeijer\Transpher\WebSocket\SendNostr::relay($client, $logger);
   
    expect($replier(['EVENT', ['id' => '12345']]))->toBeTrue();
});