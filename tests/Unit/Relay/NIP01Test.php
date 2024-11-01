<?php

describe('generic (https://nips.nostr.com/1#from-relay-to-client-sending-events-and-notices)', function () {
    it('responds with a NOTICE on null message', function () {
        expect(fn() => $recipient = \Pest\handle(null))->toThrow(\TypeError::class);
    });

    it('responds with a NOTICE on unsupported message types', function () {
        $recipient = \Pest\handle(new nostriphant\Transpher\Nostr\Message('UNKNOWN'));

        expect($recipient)->toHaveReceived(
                ['NOTICE', 'Message type UNKNOWN not supported']
        );
    });
});
