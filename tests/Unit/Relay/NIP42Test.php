<?php

it('SHOULD send the client an AUTH request with challenge when RELAY_ENABLE_AUTHENTICATION eq 1.', function () {
    putenv('RELAY_ENABLE_AUTHENTICATION=1');

    $relay = new \nostriphant\Transpher\Amp\Relay(Pest\store(), ROOT_DIR . '/data/files', true);

    $request = new \Amp\Http\Server\Request(
            mock(Amp\Http\Server\Driver\Client::class),
            'GET',
            mock(Psr\Http\Message\UriInterface::class)
    );
    $response = new Amp\Http\Server\Response();

    $client = mock(\Amp\Websocket\WebsocketClient::class);
    $client->expects('sendText')->withArgs(function (string $json) {
        $auth_message = json_decode($json);
        return $auth_message[0] === 'AUTH' && is_string($auth_message[1]);
    });
    $client->allows('getId')->andReturn(1);
    $client->allows('isClosed')->andReturnFalse();
    $client->allows('onClose');
    $client->allows('getIterator')->andReturn(new ArrayIterator([]));

    $relay->handleClient($client, $request, $response);
});
