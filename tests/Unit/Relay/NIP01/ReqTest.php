<?php

use nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Key;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use nostriphant\TranspherTests\Unit\Client;
use function Pest\context;

afterEach(fn() => Client::generic_client(true));

describe('REQ', function () {
    it('replies NOTICE Invalid message on non-existing filters', function () {
        $context = context();

        Relay::handle(json_encode(['REQ']), $context);

        expect($context->reply)->toHaveReceived(
                ['NOTICE', 'Invalid message']
        );
    });
    it('replies CLOSED on empty filters', function () {
        $context = context();

        Relay::handle(json_encode(['REQ', $id = uniqid(), []]), $context);

        expect($context->reply)->toHaveReceived(
                ['CLOSED', $id, 'Subscription filters are empty']
        );
    });
    it('can handle a subscription request, for non existing events', function () {
        $context = context();

        Relay::handle(json_encode(['REQ', $id = uniqid(), ['ids' => ['abdcd']]]), $context);

        expect($context->reply)->toHaveReceived(
                ['EOSE', $id]
        );
    });

    it('can handle a subscription request, for existing events', function () {
        $context = context();

        $sender_key = \Pest\key_sender();
        $event = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');
        Relay::handle($event, $context);
        expect($context->reply)->toHaveReceived(
                ['OK']
        );

        Relay::handle(json_encode(['REQ', $id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
        expect($context->reply)->toHaveReceived(
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
        $context = context();

        $alice_key = \Pest\key_sender();
        $event_alice = \nostriphant\Transpher\Nostr\Message\Factory::event($alice_key, 1, 'Hello world, from Alice!');
        Relay::handle($event_alice, $context);
        expect($context->reply)->toHaveReceived(
                ['OK']
        );

        $bob_key = Key::generate();
        $event_bob = \nostriphant\Transpher\Nostr\Message\Factory::event($bob_key, 1, 'Hello world, from Bob!');
        Relay::handle($event_bob, $context);
        expect($context->reply)->toHaveReceived(
                ['OK']
        );

        Relay::handle(json_encode([
            'REQ',
            $id = uniqid(), [
                'authors' => [$alice_key(Key::public())]
            ], [
                'authors' => [$bob_key(Key::public())]
    ]]), $context);

        expect($context->reply)->toHaveReceived(
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

        $context = context();

        $alice_key = \Pest\key_sender();

        Relay::handle(json_encode(['REQ', $id = uniqid(), ['authors' => [$alice_key(Key::public())]]]), $context);
        expect($context->reply)->toHaveReceived(
                ['EOSE', $id]
        );

        $key_charlie = \Pest\key_recipient();
        $event_charlie = \nostriphant\Transpher\Nostr\Message\Factory::event($key_charlie, 1, 'Hello world!');
        Relay::handle($event_charlie, $context);
        expect($context->reply)->toHaveReceived(
                ['OK']
        );

        $event = \nostriphant\Transpher\Nostr\Message\Factory::event($alice_key, 1, 'Relayable Hello worlda!');
        Relay::handle($event, $context);
        expect($context->reply)->toHaveReceived(
                ['OK']
        );
        expect($context->relay)->toHaveReceived(
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
