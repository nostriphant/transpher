<?php

use nostriphant\Transpher\Nostr\Key;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use nostriphant\TranspherTests\Feature\Functions;

describe('agent', function () : void {
    it('starts relay and sends private direct messsage to relay owner', function () : void {
        $relay = Functions::bootRelay('127.0.0.1:8085', [
                'RELAY_STORE' => ROOT_DIR . '/data/events/' . uniqid('relay_', true)
        ]);

        $agent_key = \Pest\key_sender();
        $alice_key = \Pest\key_sender();
        $agent = Functions::bootAgent(8084, [
            'RELAY_OWNER_NPUB' => $alice_key(Key::public(\nostriphant\Transpher\Nostr\Key\Format::BECH32)), 
            'AGENT_NSEC' => $agent_key(Key::private(\nostriphant\Transpher\Nostr\Key\Format::BECH32)),
            'RELAY_URL' => 'ws://127.0.0.1:8085'
        ]);
        sleep(1); // hack to give agent some time to boot...
        $alice = \nostriphant\TranspherTests\Client::client(8085);
        $subscription = Factory::subscribe(
                new Filter(tags: ['#p' => [$alice_key(Key::public())]])
        );
        $alice->expectNostrPrivateDirectMessage($subscription()[1], $alice_key, 'Hello, I am your agent! The URL of your relay is ws://127.0.0.1:8085');
        $request = $subscription();
        $alice->json($request);
        expect($request[2])->toBeArray();
        expect($request[2]['#p'])->toContain($alice_key(Key::public()));

        $alice->start();

        $agent();
        $relay();
    });
});
