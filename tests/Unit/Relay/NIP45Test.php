<?php

use nostriphant\NIP01\Key;
use nostriphant\NIP59\Rumor;
use nostriphant\NIP01\Message;

it('accepts a simple COUNT message and returns the number of matching events', function () {
    $alice_key = \Pest\key_sender();
    $bob_key = Key::generate();
    $store = \Pest\store([
        (new Rumor(time(), $alice_key(Key::public()), 1, 'Hello world, from Alice!', []))($alice_key),
        (new Rumor(time(), $bob_key(Key::public()), 1, 'Hello world, from Bob!', []))($bob_key)
    ]);

    $recipient = \Pest\handle(Message::count($id = uniqid(), [
                'authors' => [$alice_key(Key::public())]
                    ], [
                'authors' => [$bob_key(Key::public())]
            ]), store: $store);

    expect($recipient)->toHaveReceived(
        ['COUNT', $id, ['count' => 2]]
    );
});

it('refuses COUNT message without filters', function () {
    $recipient = \Pest\handle(Message::count($id = uniqid(), []));

    expect($recipient)->toHaveReceived(
            ['CLOSED', $id, 'count filters are empty']
    );
});

it('refuses COUNT message with more than max filters (default 10)', function () {
    $recipient = \Pest\handle(Message::count($id = uniqid(),
                    ['ids' => ['a']],
                ['ids' => ['a']],
                ['ids' => ['a']],
                ['ids' => ['a']],
                ['ids' => ['a']],
                ['ids' => ['a']],
                ['ids' => ['a']],
                ['ids' => ['a']],
                ['ids' => ['a']],
                ['ids' => ['a']],
                ['ids' => ['a']]
    ));

    expect($recipient)->toHaveReceived(
            ['CLOSED', $id, 'max number of filters per count (10) reached']
    );
});
