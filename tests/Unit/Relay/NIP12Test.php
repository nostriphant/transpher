<?php

use rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Key;
use function Pest\context;

/**
 * https://github.com/nostr-protocol/nips/commit/72bb8a128b2d7d3c2c654644cd68d0d0fe58a3b1#diff-8986f5dd399909df0ccb047d3bb1056061e74dcf25bc80af1cd52decf9358340
 */
describe('REQ', function () {

    $tags = [
        'p' => 'randomPTag',
        'e' => 'randomETag',
        'r' => 'randomRTag'
    ];
    foreach ($tags as $tag => $tag_value) {
        it('sends events to all clients subscribed on ' . $tag . '-tag', function () use ($tag, $tag_value) {
            $context = context();

            $sender_key = Key::generate();
            $message = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', [$tag, $tag_value]);
            Relay::handle($message, $context);

            Relay::handle(json_encode(['REQ', $id = uniqid(), ['#' . $tag => [$tag_value]]]), $context);

            expect($context->relay)->toHaveReceived(
                    ['OK'],
                    ['EVENT', $id, function (array $event) {
                            expect($event['content'])->toBe('Hello World');
                        }],
                    ['EOSE', $id]
            );
        });
    }


    it('relays events to Bob, sent after they subscribed on Alices messages', function () {
        $context = context();
        $tag = 'p';
        $tag_value = uniqid();

        Relay::handle(json_encode(['REQ', $id = uniqid(), ['#' . $tag => [$tag_value]]]), $context);

        $sender_key = Key::generate();
        $message = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', [$tag, $tag_value]);
        Relay::handle($message, $context);

        expect($context->relay)->toHaveReceived(
                ['EOSE', $id],
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $id],
                ['OK']
        );
    });

    it('sends events to all clients subscribed on p after restarting the server', function () {
        $tag = 'p';
        $tag_value = uniqid();
        $sender_key = Key::generate();
        $message = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', [$tag, $tag_value]);

        $context = context([
            new \rikmeijer\Transpher\Nostr\Event(...$message()[1])
        ]);

        Relay::handle(json_encode(['REQ', $id = uniqid(), ['#' . $tag => [$tag_value]]]), $context);

        expect($context->relay)->toHaveReceived(
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $id]
        );
    });
});
