<?php

use nostriphant\NIP01\Key;
use nostriphant\TranspherTests\Factory;
use nostriphant\NIP01\Message;

describe('REQ', function () {
    it('replies NOTICE Invalid message on non-existing filters', function () {
        

        $recipient = \Pest\handle(new \nostriphant\NIP01\Message('REQ'));

        expect($recipient)->toHaveReceived(
                ['NOTICE', 'Invalid message']
        );
    });
    it('replies CLOSED on empty filters', function () {
        

        $recipient = \Pest\handle(Message::req($id = uniqid(), []));

        expect($recipient)->toHaveReceived(
                ['CLOSED', $id, 'subscription filters are empty']
        );
    });
    it('can handle a subscription request, for non existing events', function () {
        

        $recipient = \Pest\handle(Message::req($id = uniqid(), ['ids' => ['abdcd']]));

        expect($recipient)->toHaveReceived(
                ['EOSE', $id]
        );
    });

    it('does not break with a broken REQ messagae', function () {
        $recipient = \Pest\handle(new nostriphant\NIP01\Message('REQ', 'some-subscription-id', 'REQ'));

        expect($recipient)->toHaveReceived(
                ['CLOSED']
        );
    });

    it('can handle a subscription request, for existing events', function () {
        $store = \Pest\store();

        $sender_key = \Pest\key_sender();
        $event = Factory::event($sender_key, 1, 'Hello World');
        $recipient = \Pest\handle($event, store: $store);
        expect($recipient)->toHaveReceived(
                ['OK']
        );

        $recipient = \Pest\handle(Message::req($id = uniqid(), ['authors' => [$sender_key(Key::public())]]), store: $store);
        expect($recipient)->toHaveReceived(
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $id]
        );
    });

    it('sends events to all clients subscribed on event id', function () {
        $store = \Pest\store();

        $alice_key = \Pest\key_sender();
        $alice_event = Factory::event($alice_key, 1, 'Hello worlda!');
        $alice = \Pest\handle($alice_event, store: $store);
        expect($alice)->toHaveReceived(
                ['OK', $alice_event()[1]['id'], true]
        );


        $key_charlie = \Pest\key_recipient();
        $note2 = Factory::event($key_charlie, 1, 'Hello worldi!');
        $charlie = \Pest\handle($note2, store: $store);
        expect($charlie)->toHaveReceived(
                ['OK', $note2()[1]['id'], true]
        );

        $subscription = Factory::subscribe(
                ["ids" => [$note2()[1]['id']]]
        );
        $bob = \Pest\handle($subscription, store: $store);
        expect($bob)->toHaveReceived(
                ['EVENT', $subscription()[1], function (array $event) {
                        expect($event['content'])->toBe('Hello worldi!');
                    }],
                ['EOSE', $subscription()[1]]
        );
    });

    it('sends events to all clients subscribed on author (pubkey)', function () {
        $store = \Pest\store();

        $alice_key = \Pest\key_sender();
        $alice_event = Factory::event($alice_key, 1, 'Hello world!');
        $alice = \Pest\handle($alice_event, store: $store);
        expect($alice)->toHaveReceived(
                ['OK', $alice_event()[1]['id'], true]
        );

        $subscription = Factory::subscribe(
                ["authors" => [$alice_key(Key::public())]]
        );
        $bob = \Pest\handle($subscription, store: $store);
        expect($bob)->toHaveReceived(
                ['EVENT', $subscription()[1], function (array $event) {
                        expect($event['content'])->toBe('Hello world!');
                    }],
                ['EOSE', $subscription()[1]]
        );
    });

    it('sends events to Charlie who uses two filters in their subscription', function () {
        $store = \Pest\store();
        $subscriptions = \Pest\subscriptions();

        $alice_key = \Pest\key_sender();
        $event_alice = Factory::event($alice_key, 1, 'Hello world, from Alice!');
        $recipient = \Pest\handle($event_alice, store: $store, subscriptions: $subscriptions);
        expect($recipient)->toHaveReceived(
                ['OK']
        );

        $bob_key = Key::generate();
        $event_bob = Factory::event($bob_key, 1, 'Hello world, from Bob!');
        $recipient = \Pest\handle($event_bob, store: $store, subscriptions: $subscriptions);
        expect($recipient)->toHaveReceived(
                ['OK']
        );

        $recipient = \Pest\handle(Message::req($id = uniqid(), [
                    'authors' => [$alice_key(Key::public())]
            ], [
                'authors' => [$bob_key(Key::public())]
        ]), store: $store, subscriptions: $subscriptions);

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
        $store = \Pest\store();
        $relay = \Pest\relay();
        $subscriptions = \Pest\subscriptions(relay: $relay);

        $alice_key = \Pest\key_sender();
        $alice_event = Factory::event($alice_key, 1, 'Hello world!');
        $alice = \Pest\handle($alice_event, store: $store);
        expect($alice)->toHaveReceived(
                ['OK', $alice_event()[1]['id'], true]
        );

        $subscription = Factory::subscribe(
                ["authors" => [$alice_key(Key::public())]]
        );
        $bob = \Pest\handle($subscription, store: $store, subscriptions: $subscriptions);
        expect($bob)->toHaveReceived(
                ['EVENT', $subscription()[1], function (array $event) {
                        expect($event['content'])->toBe('Hello world!');
                    }],
                ['EOSE', $subscription()[1]]
        );

        $bob = \Pest\handle(Message::close($subscription()[1]), store: $store, subscriptions: $subscriptions);
        expect($bob)->toHaveReceived(
                ['CLOSED', $subscription()[1], '']
        );

        $alice_event2 = Factory::event($alice_key, 1, 'Hello world!');
        $alice = \Pest\handle($alice_event2, store: $store);
        expect($alice)->toHaveReceived(
                ['OK', $alice_event2()[1]['id'], true]
        );

        expect($relay)->toHaveReceivedNothing();
    });

    it('sends events to all clients subscribed on kind', function () {
        $store = \Pest\store();

        $alice_key = \Pest\key_sender();
        $alice_event = Factory::event($alice_key, 3, 'Hello world!');
        $alice = \Pest\handle($alice_event, store: $store);
        expect($alice)->toHaveReceived(
                ['OK', $alice_event()[1]['id'], true]
        );

        $subscription = Factory::subscribe(
               ["kinds" => [3]]
        );
        $bob = \Pest\handle($subscription, store: $store);
        expect($bob)->toHaveReceived(
                ['EVENT', $subscription()[1], function (array $event) {
                        expect($event['content'])->toBe('Hello world!');
                    }],
                ['EOSE', $subscription()[1]]
        );
    });

    it('relays events to Bob, sent after they subscribed on Alices messages', function () {
        $store = \Pest\store();
        $relay = \Pest\relay();
        $subscriptions = \Pest\subscriptions(relay: $relay);

        $alice_key = \Pest\key_sender();

        $subscription = Message::req($id = uniqid(), ['authors' => [$alice_key(Key::public())]]);
        $recipient = \Pest\handle($subscription, store: $store, subscriptions: $subscriptions);
        expect($recipient)->toHaveReceived(
                ['EOSE', $id]
        );

        $key_charlie = \Pest\key_recipient();
        $event_charlie = Factory::event($key_charlie, 1, 'Hello world!');
        $recipient = \Pest\handle($event_charlie, store: $store);
        expect($recipient)->toHaveReceived(
                ['OK']
        );

        $event = Factory::event($alice_key, 1, 'Relayable Hello worlda!');
        $recipient = \Pest\handle($event, store: $store, subscriptions: $subscriptions);
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
        $store = \Pest\store();

        $alice_key = \Pest\key_sender();
        $alice_event = Factory::event($alice_key, 1, 'Hello wirld!');
        $alice = \Pest\handle($alice_event, store: $store);
        expect($alice)->toHaveReceived(
                ['OK', $alice_event()[1]['id'], true]
        );

        expect(isset($store[$alice_event()[1]['id']]))->toBeTrue();

        $subscription = Factory::subscribe(
                ["authors" => [$alice_key(Key::public())]]
                );
        $bob = \Pest\handle($subscription, store: $store);
        expect($bob)->toHaveReceived(
                ['EVENT', $subscription()[1], function (array $event) {
                        expect($event['content'])->toBe('Hello wirld!');
                    }],
                ['EOSE', $subscription()[1]]
        );
    });
});
