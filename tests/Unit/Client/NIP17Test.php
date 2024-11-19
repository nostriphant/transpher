<?php

use nostriphant\NIP01\Key;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Event\Gift;
use nostriphant\Transpher\Nostr\Event\Seal;
use function Pest\incoming;

it('relays private direct messsage from alice to bob', function (): void {
    putenv('LIMIT_EVENT_CREATED_AT_LOWER_DELTA=' . (60 * 60 * 72));
    $store = \Pest\store();
    $alice_key = \Pest\key_sender();

    $bob_key = \Pest\key_recipient();
    $event = Factory::privateDirect($alice_key, $bob_key(Key::public(Key\Format::HEXIDECIMAL)), 'Hello!!');

    expect(\Pest\handle($event, incoming(store: $store)))->toHaveReceived(
            ['OK', $event()[1]['id'], true]
    );

    $recipient = \Pest\handle(Factory::req($subscriptionId = uniqid(), ['#p' => [$bob_key(Key::public())]]), incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['EVENT', $subscriptionId, function (array $gift) use ($bob_key) {
                    expect($gift['kind'])->toBe(1059);

                    $seal = Gift::unwrap($bob_key, $gift['pubkey'], $gift['content']);
                    expect($seal['kind'])->toBe(13);
                    expect($seal['pubkey'])->toBeString();
                    expect($seal['content'])->toBeString();

                    $private_message = Seal::open($bob_key, $seal['pubkey'], $seal['content']);
                    expect($private_message)->toBeArray();
                    expect($private_message)->toHaveKey('id');
                    expect($private_message)->toHaveKey('content');
                    expect($private_message['content'])->toBe('Hello!!');
                }],
            ['EOSE', $subscriptionId]
    );
    putenv('LIMIT_EVENT_CREATED_AT_LOWER_DELTA');
});
