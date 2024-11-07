<?php

use nostriphant\Transpher\Nostr\Key;

use nostriphant\Transpher\Nostr\Event\Factory as EventFactory;
use nostriphant\Transpher\Nostr\Message\Factory as MessageFactory;

it('accepts a simple COUNT message and returns the number of matching events', function () {
    $alice_key = \Pest\key_sender();
    $bob_key = Key::generate();
    $store = \Pest\store([
        EventFactory::event($alice_key, 1, 'Hello world, from Alice!'),
        EventFactory::event($bob_key, 1, 'Hello world, from Bob!')
    ]);

    $recipient = \Pest\handle(MessageFactory::countRequest($id = uniqid(), [
                'authors' => [$alice_key(Key::public())]
                    ], [
                'authors' => [$bob_key(Key::public())]
            ]), \Pest\incoming(store: $store));

    expect($recipient)->toHaveReceived(
        ['COUNT', $id, ['count' => 2]]
    );
});

it('refuses COUNT message without filters', function () {
    $recipient = \Pest\handle(MessageFactory::countRequest($id = uniqid(), []));

    expect($recipient)->toHaveReceived(
            ['CLOSED', $id, 'count filters are empty']
    );
});

it('refuses COUNT message with more than max filters (default 10)', function () {
    $recipient = \Pest\handle(MessageFactory::countRequest($id = uniqid(),
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
