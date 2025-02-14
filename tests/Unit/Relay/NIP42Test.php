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


    $auth_rumor = $challenge = null;
    $client = mock(\Amp\Websocket\WebsocketClient::class);
    $client->expects('sendText')->twice()->withArgs(function (string $json) use (&$challenge, &$auth_rumor) {
        $auth_message = json_decode($json);
        $challenge = $auth_message[1];

        return match ($auth_message[0]) {
            'AUTH' => is_string($auth_message[1]),
            'OK' => $auth_message[1] === $auth_rumor->id && $auth_message[2] === true
        };
    });
    $client->allows('getId')->andReturn(1);
    $client->allows('isClosed')->andReturnFalse();
    $client->allows('onClose');
    $client->allows('getIterator')->andReturnUsing(function () use (&$challenge, &$auth_rumor) {

        $client_key = \Pest\key_sender();
        $auth_rumor = (new \nostriphant\NIP59\Rumor(time(), $client_key(nostriphant\NIP01\Key::public()), 22242, '', [
                    ["relay", ""],
                    ["challenge", $challenge]
        ]));
        return new ArrayIterator([
            \nostriphant\NIP01\Message::auth($auth_rumor($client_key))
        ]);
    });

    $relay->handleClient($client, $request, $response);
});
