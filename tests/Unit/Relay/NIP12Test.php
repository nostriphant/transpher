<?php

use nostriphant\Transpher\Relay;
use function Pest\context;

/**
 * https://github.com/nostr-protocol/nips/commit/72bb8a128b2d7d3c2c654644cd68d0d0fe58a3b1#diff-8986f5dd399909df0ccb047d3bb1056061e74dcf25bc80af1cd52decf9358340
 */
describe('REQ', function () {
    it('sends events to all clients subscribed on tag', function ($tag, $tag_value) {
        $context = context();

        $sender_key = \Pest\key_sender();
        $message = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', [$tag, $tag_value]);
        $recipient = \Pest\handle($message, $context);
        expect($recipient)->toHaveReceived(
                ['OK']
        );

        $recipient = \Pest\handle(new \nostriphant\Transpher\Nostr\Message('REQ', $id = uniqid(), ['#' . $tag => [$tag_value]]), $context);
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
        $context = context();
        $tag = 'p';
        $tag_value = uniqid();

        $recipient = \Pest\handle(new \nostriphant\Transpher\Nostr\Message('REQ', $id = uniqid(), ['#' . $tag => [$tag_value]]), $context);
        expect($recipient)->toHaveReceived(
                ['EOSE', $id],
        );

        $sender_key = \Pest\key_sender();
        $message = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', [$tag, $tag_value]);
        $recipient = \Pest\handle($message, $context);
        expect($context->relay)->toHaveReceived(
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
        $message = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', [$tag, $tag_value]);

        $context = context([
            new \nostriphant\Transpher\Nostr\Event(...$message()[1])
        ]);

        $recipient = \Pest\handle(new \nostriphant\Transpher\Nostr\Message('REQ', $id = uniqid(), ['#' . $tag => [$tag_value]]), $context);

        expect($recipient)->toHaveReceived(
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $id]
        );
    });
});
