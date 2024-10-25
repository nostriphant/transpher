<?php

use rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Key;
use function Pest\context;

/**
 * https://github.com/nostr-protocol/nips/commit/72bb8a128b2d7d3c2c654644cd68d0d0fe58a3b1#diff-cb35fa3408b4c7c7f9795f7ed428ae143184a454e536ed853abf7d0b672823fdL26
 */
it('accepts a kind 1 and answers with OK', function () {
    $context = context();

    $sender_key = Key::generate();
    $message = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');
    Relay::handle($message, $context);

    expect($context->relay)->toHaveReceived(
            ['OK', $message()[1]['id'], true, '']
    );
});

it('rejects a kind 1 and answers with OK, false, when signature is wrong', function () {
    $context = context();

    $sender_key = Key::generate();
    $message = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');
    $message_raw = $message();
    $message_raw[1]['sig'] = 'improper signature here';
    Relay::handle(json_encode($message_raw), $context);

    expect($context->relay)->toHaveReceived(
            ['OK', $message()[1]['id'], false, 'invalid:signature is wrong']
    );
});
