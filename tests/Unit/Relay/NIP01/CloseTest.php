<?php

use nostriphant\Transpher\Relay;
use function Pest\context;

describe('CLOSE', function () {
    it('responds with a NOTICE on missing subscription-id', function () {
        $context = context();

        $recipient = \Pest\handle(new \nostriphant\Transpher\Nostr\Message('CLOSE'), $context);

        expect($recipient)->toHaveReceived(
                ['NOTICE', 'Missing subscription ID']
        );
    });
});
