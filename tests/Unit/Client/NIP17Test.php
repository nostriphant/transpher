<?php

use nostriphant\Transpher\Nostr\Key;
use nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Event\Gift;
use nostriphant\Transpher\Nostr\Event\Seal;
use function \Pest\context;

it('relays private direct messsage from alice to bob', function (): void {

    $context = context();

    $alice_key = \Pest\key_sender();

    $bob_key = \Pest\key_recipient();
    $event = \nostriphant\Transpher\Nostr\Message\Factory::privateDirect($alice_key, $bob_key(Key::public(Key\Format::HEXIDECIMAL)), 'Hello!!');

    Relay::handle($event, $context);
    expect($context->reply)->toHaveReceived(
            ['OK']
    );

    Relay::handle(new \nostriphant\Transpher\Nostr\Message('REQ', $subscriptionId = uniqid(), ['#p' => [$bob_key(Key::public())]]), $context);
    expect($context->reply)->toHaveReceived(
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
});
