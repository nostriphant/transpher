<?php

use nostriphant\Transpher\Relay;
use function Pest\context;

describe('generic (https://nips.nostr.com/1#from-relay-to-client-sending-events-and-notices)', function () {
    it('responds with a NOTICE on null message', function () {
        $context = context();

        expect(fn() => $recipient = \Pest\handle(null, $context))->toThrow(\TypeError::class);
    });

    it('responds with a NOTICE on unsupported message types', function () {
        $context = context();

        $recipient = \Pest\handle(new nostriphant\Transpher\Nostr\Message('UNKNOWN'), $context);

        expect($recipient)->toHaveReceived(
                ['NOTICE', 'Message type UNKNOWN not supported']
        );
    });
});
