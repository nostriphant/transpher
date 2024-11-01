<?php

use nostriphant\Transpher\Nostr\Message\Factory;

/**
 * https://github.com/nostr-protocol/nips/commit/72bb8a128b2d7d3c2c654644cd68d0d0fe58a3b1#diff-cb35fa3408b4c7c7f9795f7ed428ae143184a454e536ed853abf7d0b672823fdL26
 */
it('accepts a kind 1 and answers with OK', function () {
    

    $sender_key = \Pest\key_sender();
    $message = Factory::event($sender_key, 1, 'Hello World');
    $recipient = \Pest\handle($message);

    expect($recipient)->toHaveReceived(
            ['OK', $message()[1]['id'], true, '']
    );
});

it('rejects a kind 1 and answers with OK, false, when signature is wrong', function () {
    

    $sender_key = \Pest\key_sender();
    $message = Factory::event($sender_key, 1, 'Hello World');
    $message_raw = $message();
    $message_raw[1]['sig'] = 'improper signature here';
    $recipient = \Pest\handle(new nostriphant\Transpher\Nostr\Message(...$message_raw));

    expect($recipient)->toHaveReceived(
            ['OK', $message()[1]['id'], false, 'invalid:signature is wrong']
    );
});
