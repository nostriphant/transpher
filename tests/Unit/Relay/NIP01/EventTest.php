<?php

use rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Key;

describe('EVENT', function () {
    it('accepts a kind 1 and answers with OK', function () {
        $context = context();

        $sender_key = Key::generate();
        $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');
        Relay::handle($event, $context);

        expect($context->relay)->toHaveReceived(
                ['OK', $event()[1]['id'], true, '']
        );
    });
});
