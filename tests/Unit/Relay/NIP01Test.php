<?php

use rikmeijer\Transpher\Relay;

describe('generic (https://nips.nostr.com/1#from-relay-to-client-sending-events-and-notices)', function () {
    it('responds with a NOTICE on null message', function () {
        $context = context();

        Relay::handle('null', $context);

        expect($context->relay)->toHaveReceived(
                ['NOTICE', 'Invalid message']
        );
    });

    it('responds with a NOTICE on unsupported message types', function () {
        $context = context();

        Relay::handle('["UNKNOWN"]', $context);

        expect($context->relay)->toHaveReceived(
                ['NOTICE', 'Message type UNKNOWN not supported']
        );
    });
});
