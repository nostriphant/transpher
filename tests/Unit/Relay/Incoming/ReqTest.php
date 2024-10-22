<?php

it('can handle a Incoming Req', function () {
    $relay = Mockery::mock(\rikmeijer\Transpher\Relay\Sender::class)->allows([
        '__invoke' => true
    ]);

    $store = Mockery::mock(\rikmeijer\Transpher\Relay\Store::class);
    $store->shouldReceive('__invoke')->andReturn([]);

    $incoming = new rikmeijer\Transpher\Relay\Incoming\Req('some-subscription-id', ['ids' => ['abdcd']]);
    $expected_messages = ['EOSE'];
    foreach ($incoming(['events' => $store, 'relay' => $relay]) as $message) {
        $expected_message = array_shift($expected_messages);
        expect($message->type)->toBe($expected_message);
    }
    expect($expected_messages)->toBeEmpty();
});
