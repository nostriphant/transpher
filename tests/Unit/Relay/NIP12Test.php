<?php

use nostriphant\TranspherTests\Factory;
use function Pest\store;
use nostriphant\NIP01\Event;
use nostriphant\NIP01\Message;

/**
 * https://github.com/nostr-protocol/nips/commit/72bb8a128b2d7d3c2c654644cd68d0d0fe58a3b1#diff-8986f5dd399909df0ccb047d3bb1056061e74dcf25bc80af1cd52decf9358340
 */
describe('REQ', function () {
    it('sends events to all clients subscribed on tag', function ($tag, $tag_value) {
        $store = \Pest\store();

        $sender_key = \Pest\key_sender();
        $message = Factory::event($sender_key, 1, 'Hello World', [$tag, $tag_value]);
        $recipient = \Pest\handle($message, store: $store);
        expect($recipient)->toHaveReceived(
                ['OK']
        );

        $recipient = \Pest\handle(Message::req($id = uniqid(), ['#' . $tag => [$tag_value]]), store: $store);
        expect($recipient)->toHaveReceived(
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $id]
        );
    })->with([
        ['p', 'randomPTag'],
        ['e', 'randomETag'],
        ['r', 'randomRTag']
    ]);

    it('relays events to Bob, sent after they subscribed on Alices messages', function () {
        $relay = \Pest\relay();
        $store = \Pest\store();
        $subscriptions = \Pest\subscriptions(relay: $relay);

        $tag = 'p';
        $tag_value = uniqid();

        $recipient = \Pest\handle(Message::req($id = uniqid(), ['#' . $tag => [$tag_value]]), store: $store, subscriptions: $subscriptions);
        expect($recipient)->toHaveReceived(
                ['EOSE', $id],
        );

        $sender_key = \Pest\key_sender();
        $message = Factory::event($sender_key, 1, 'Hello World', [$tag, $tag_value]);
        $recipient = \Pest\handle($message, store: $store, subscriptions: $subscriptions);
        expect($relay)->toHaveReceived(
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $id],
        );
        expect($recipient)->toHaveReceived(
                ['OK']
        );
    });

    it('sends events to all clients subscribed on p after restarting the server', function () {
        $tag = 'p';
        $tag_value = uniqid();
        $sender_key = \Pest\key_sender();
        $message = Factory::event($sender_key, 1, 'Hello World', [$tag, $tag_value]);

        $recipient = \Pest\handle(Message::req($id = uniqid(), ['#' . $tag => [$tag_value]]), store: store([
            new Event(...$message()[1])
        ]));

        expect($recipient)->toHaveReceived(
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $id]
        );
    });
});
