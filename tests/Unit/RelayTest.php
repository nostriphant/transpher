<?php

use rikmeijer\Transpher\Nostr\Key;
use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\Nostr\Subscription\Filter;
use rikmeijer\TranspherTests\Unit\Client;


it('relays private direct messsage from alice to bob', function (): void {
    $transpher_store = ROOT_DIR . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . uniqid();
    mkdir($transpher_store);

    $alice = Client::persistent_client($transpher_store);
    $alice_key = Key::generate();

    $bob_key = Key::generate();

    $alice->privateDirectMessage($alice_key, $bob_key(Key::public(Key\Format::BECH32)), 'Hello!!');

    $subscription = Factory::subscribe(
            new Filter(tags: ['#p' => [$bob_key(Key::public())]])
    );

    $bob = Client::persistent_client($transpher_store);
    $bob->expectNostrPrivateDirectMessage($subscription()[1], $bob_key, 'Hello!!');
    $request = $subscription();
    expect($request[2])->toBeArray();
    expect($request[2]['#p'])->toContain($bob_key(Key::public()));
    $bob->json($request);
    $bob->start();
});
