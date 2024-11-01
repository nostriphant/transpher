<?php

use nostriphant\Transpher\Nostr\Key;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use nostriphant\TranspherTests\Unit\Client;
use function Pest\incoming;

afterEach(fn() => Client::generic_client(true));

describe('REQ', function () {
    it('replies NOTICE Invalid message on non-existing filters', function () {
        

        $recipient = \Pest\handle(new \nostriphant\Transpher\Nostr\Message('REQ'));

        expect($recipient)->toHaveReceived(
                ['NOTICE', 'Invalid message']
        );
    });
    it('replies CLOSED on empty filters', function () {
        

        $recipient = \Pest\handle(Factory::req($id = uniqid(), []));

        expect($recipient)->toHaveReceived(
                ['CLOSED', $id, 'Subscription filters are empty']
        );
    });
    it('can handle a subscription request, for non existing events', function () {
        

        $recipient = \Pest\handle(Factory::req($id = uniqid(), ['ids' => ['abdcd']]));

        expect($recipient)->toHaveReceived(
                ['EOSE', $id]
        );
    });

    it('can handle a subscription request, for existing events', function () {
        $store = \Pest\store();

        $sender_key = \Pest\key_sender();
        $event = Factory::event($sender_key, 1, 'Hello World');
        $recipient = \Pest\handle($event, incoming(store: $store));
        expect($recipient)->toHaveReceived(
                ['OK']
        );

        $recipient = \Pest\handle(Factory::req($id = uniqid(), ['authors' => [$sender_key(Key::public())]]), incoming(store: $store));
        expect($recipient)->toHaveReceived(
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $id]
        );
    });

    it('sends events to all clients subscribed on event id', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        $alice_key = \Pest\key_sender();
        $alice->sendSignedMessage(Factory::event($alice_key, 1, 'Hello worlda!'));

        $key_charlie = \Pest\key_recipient();
                $note2 = Factory::event($key_charlie, 1, 'Hello worldi!');
        $alice->sendSignedMessage($note2);

        $subscription = Factory::subscribe(
                new Filter(ids: [$note2()[1]['id']])
        );

        $bob->expectNostrEvent($subscription()[1], 'Hello worldi!');
        $bob->expectNostrEose($subscription()[1]);
        $bob->json($subscription());
        $bob->start();
    });

    it('sends events to all clients subscribed on author (pubkey)', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        $alice_key = \Pest\key_sender();
        $alice->sendSignedMessage(Factory::event($alice_key, 1, 'Hello world!'));
        $subscription = Factory::subscribe(
                new Filter(authors: [$alice_key(Key::public())])
        );

        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $bob->json($subscription());
        $bob->start();
    });

    it('sends events to Charlie who uses two filters in their subscription', function () {
        $store = \Pest\store();
        $subscriptions = \Pest\subscriptions();

        $alice_key = \Pest\key_sender();
        $event_alice = Factory::event($alice_key, 1, 'Hello world, from Alice!');
        $recipient = \Pest\handle($event_alice, incoming(store: $store, subscriptions: $subscriptions));
        expect($recipient)->toHaveReceived(
                ['OK']
        );

        $bob_key = Key::generate();
        $event_bob = Factory::event($bob_key, 1, 'Hello world, from Bob!');
        $recipient = \Pest\handle($event_bob, incoming(store: $store, subscriptions: $subscriptions));
        expect($recipient)->toHaveReceived(
                ['OK']
        );

        $recipient = \Pest\handle(Factory::req($id = uniqid(), [
                    'authors' => [$alice_key(Key::public())]
            ], [
                'authors' => [$bob_key(Key::public())]
        ]), incoming(store: $store, subscriptions: $subscriptions));

        expect($recipient)->toHaveReceived(
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello world, from Alice!');
                    }],
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello world, from Bob!');
                    }],
                ['EOSE', $id]
        );
    });

    it('closes subscription and stop sending events to subscribers', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();
        $alice_key = \Pest\key_sender();

        $alice->sendSignedMessage(Factory::event($alice_key, 1, 'Hello world!'));

        $subscription = Factory::subscribe(
                new Filter(authors: [$alice_key(Key::public())])
        );
        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $bob->json($subscription());
        $bob->start();

        $bob->expectNostrClosed($subscription()[1], '');

        $request = Factory::close($subscription()[1]);
        $bob->send($request);
        $bob->start();
    });

    it('sends events to all clients subscribed on kind', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();
        $alice_key = \Pest\key_sender();

        $alice->sendSignedMessage(Factory::event($alice_key, 3, 'Hello world!'));

        $subscription = Factory::subscribe(
                new Filter(kinds: [3])
        );

        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $bob->json($subscription());
        $bob->start();
    });

    it('relays events to Bob, sent after they subscribed on Alices messages', function () {
        $store = \Pest\store();
        $relay = \Pest\relay();
        $subscriptions = \Pest\subscriptions(relay: $relay);

        $alice_key = \Pest\key_sender();

        $recipient = \Pest\handle(Factory::req($id = uniqid(), ['authors' => [$alice_key(Key::public())]]), incoming(store: $store, subscriptions: $subscriptions));
        expect($recipient)->toHaveReceived(
                ['EOSE', $id]
        );

        $key_charlie = \Pest\key_recipient();
        $event_charlie = Factory::event($key_charlie, 1, 'Hello world!');
        $recipient = \Pest\handle($event_charlie, incoming(store: $store));
        expect($recipient)->toHaveReceived(
                ['OK']
        );

        $event = Factory::event($alice_key, 1, 'Relayable Hello worlda!');
        $recipient = \Pest\handle($event, incoming(store: $store, subscriptions: $subscriptions));
        expect($recipient)->toHaveReceived(
                ['OK']
        );
        expect($relay)->toHaveReceived(
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Relayable Hello worlda!');
                    }],
                ['EOSE', $id]
        );
    });

    it('sends events to all clients subscribed on author (pubkey), even after restarting the server', function () {
        $transpher_store = ROOT_DIR . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . uniqid();
        mkdir($transpher_store);

        $alice = Client::persistent_client($transpher_store);

        $alice_key = \Pest\key_sender();
        $alice->sendSignedMessage($alice_event = Factory::event($alice_key, 1, 'Hello wirld!'));

        $event_file = $transpher_store . DIRECTORY_SEPARATOR . $alice_event()[1]['id'] . '.php';
        expect(is_file($event_file))->toBeTrue($event_file);

        $bob = Client::persistent_client($transpher_store);
        $subscription = Factory::subscribe(
                new Filter(authors: [$alice_key(Key::public())])
        );

        $bob->expectNostrEvent($subscription()[1], 'Hello wirld!');
        $bob->expectNostrEose($subscription()[1]);

        $bob->json($subscription());
        $bob->start();

        unlink($event_file);
        rmdir($transpher_store);
    });
});
