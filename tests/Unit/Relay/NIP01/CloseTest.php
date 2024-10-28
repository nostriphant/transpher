<?php

use rikmeijer\Transpher\Relay;
use function Pest\context;

describe('CLOSE', function () {
    it('responds with a NOTICE on missing subscription-id', function () {
        $context = context();

        Relay::handle(json_encode(['CLOSE']), $context);

        expect($context->reply)->toHaveReceived(
                ['NOTICE', 'Missing subscription ID']
        );
    });
});
