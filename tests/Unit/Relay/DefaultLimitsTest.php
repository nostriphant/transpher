<?php

use nostriphant\Transpher\Nostr\Message\Factory;

// https://nostr-nips.com/nip-22
it('SHOULD ignore created_at limits for regular events', function () {
    $recipient_past = \Pest\handle($message_past = Factory::eventAt(\Pest\key_sender(), 1, 'Hello World', time() - (60 * 60 * 24) - 5));
    expect($recipient_past)->toHaveReceived(
            ['OK', $message_past()[1]['id'], true]
    );

    $recipient_future = \Pest\handle($message_future = Factory::eventAt(\Pest\key_sender(), 1, 'Hello World', time() + (60 * 15) + 5));
    expect($recipient_future)->toHaveReceived(
            ['OK', $message_future()[1]['id'], true]
    );
});

it('SHOULD send the client an OK result saying the event was not stored for the created_at timestamp not being within the permitted limits.', function (int $kind) {
    $recipient_past = \Pest\handle($message_past = Factory::eventAt(\Pest\key_sender(), $kind, 'Hello World', time() - (60 * 60 * 24) - 5));
    expect($recipient_past)->toHaveReceived(
            ['OK', $message_past()[1]['id'], false, 'invalid:the event created_at field is out of the acceptable range (-24h) for this relay']
    );

    $recipient_future = \Pest\handle($message_future = Factory::eventAt(\Pest\key_sender(), $kind, 'Hello World', time() + (60 * 15) + 5));
    expect($recipient_future)->toHaveReceived(
            ['OK', $message_future()[1]['id'], false, 'invalid:the event created_at field is out of the acceptable range (+15min) for this relay']
    );
})->with([
    'replaceable' => 0,
    'ephemeral' => 20000,
    'addressable' => 30000
]);


it('SHOULD deny more subscriptions than 10 per client, by default', function () {
    $subscriptions = \Pest\subscriptions();

    for ($s = 1; $s < 11; $s++) {
        $recipient_past = \Pest\handle($message_past = Factory::req('subscription' . $s . '-id', ['ids' => ['sdsd']]), subscriptions: $subscriptions);
        expect($recipient_past)->toHaveReceived(['EOSE', 'subscription' . $s . '-id']);
    }


    $recipient_past = \Pest\handle($message_past = Factory::req('subscription11-id', ['ids' => ['sdsd']]), subscriptions: $subscriptions);
    expect($recipient_past)->toHaveReceived(['CLOSED', 'subscription11-id', 'max number of subscriptions per client (10) reached']);
});
