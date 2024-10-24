<?php

use rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Key;

describe('REQ', function () {
    it('replies NOTICE Invalid message on non-existing filters', function () {
        $context = context();

        Relay::handle(json_encode(['REQ']), $context);

        expect($context->relay)->toHaveReceived(
                ['NOTICE', 'Invalid message']
        );
    });
    it('replies CLOSED on empty filters', function () {
        $context = context();

        Relay::handle(json_encode(['REQ', $id = uniqid(), []]), $context);

        expect($context->relay)->toHaveReceived(
                ['CLOSED', $id, 'Subscription filters are empty']
        );
    });
    it('can handle a subscription request, for non existing events', function () {
        $context = context();

        Relay::handle(json_encode(['REQ', $id = uniqid(), ['ids' => ['abdcd']]]), $context);

        expect($context->relay)->toHaveReceived(
                ['EOSE', $id]
        );
    });

    it('can handle a subscription request, for existing events', function () {
        $context = context();

        $sender_key = Key::generate();
        $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');
        Relay::handle($event, $context);

        Relay::handle(json_encode(['REQ', $id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);

        expect($context->relay)->toHaveReceived(
                ['OK'],
                ['EVENT', $id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $id]
        );
    });
});
