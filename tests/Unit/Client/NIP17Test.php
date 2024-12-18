<?php

use nostriphant\NIP01\Key;
use nostriphant\NIP01\Message;
use nostriphant\NIP59\Gift;
use nostriphant\NIP59\Seal;
use function Pest\incoming;
use nostriphant\NIP01\Event;

it('relays private direct messsage from alice to bob', function (): void {
    putenv('LIMIT_EVENT_CREATED_AT_LOWER_DELTA=' . (60 * 60 * 72));
    $store = \Pest\store();
    $alice_key = \Pest\key_sender();

    $bob_key = \Pest\key_recipient();
    $event = nostriphant\Transpher\Nostr\Message\PrivateDirect::make($alice_key, $bob_key(Key::public()), 'Hello!!');

    expect(\Pest\handle($event, incoming(store: $store)))->toHaveReceived(
            ['OK', $event()[1]['id'], true]
    );

    $recipient = \Pest\handle(Message::req($subscriptionId = uniqid(), ['#p' => [$bob_key(Key::public())]]), incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['EVENT', $subscriptionId, function (array $gift) use ($bob_key) {
                    expect($gift['kind'])->toBe(1059);

                    $seal = Gift::unwrap($bob_key, Event::__set_state($gift));
                    expect($seal->kind)->toBe(13);
                    expect($seal->pubkey)->toBeString();
                    expect($seal->content)->toBeString();

                    $private_message = Seal::open($bob_key, $seal);
                    expect($private_message)->toHaveKey('id');
                    expect($private_message)->toHaveKey('content');
                    expect($private_message->content)->toBe('Hello!!');
                }],
            ['EOSE', $subscriptionId]
    );
    putenv('LIMIT_EVENT_CREATED_AT_LOWER_DELTA');
});
