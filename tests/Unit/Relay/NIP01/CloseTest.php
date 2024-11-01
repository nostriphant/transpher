<?php

use nostriphant\Transpher\Relay;
use function Pest\context;

describe('CLOSE', function () {
    it('responds with a NOTICE on missing subscription-id', function () {
        $context = context();

        \Pest\handle(new \nostriphant\Transpher\Nostr\Message('CLOSE'), $context);

        expect($context->reply)->toHaveReceived(
                ['NOTICE', 'Missing subscription ID']
        );
    });
});
