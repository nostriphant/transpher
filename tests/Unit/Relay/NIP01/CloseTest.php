<?php

use rikmeijer\Transpher\Relay;

describe('CLOSE', function () {
    it('responds with a NOTICE on missing subscription-id', function () {
        $context = context();

        Relay::handle(json_encode(['CLOSE']), $context);

        expect($context->relay)->toHaveReceived(
                ['NOTICE', 'Missing subscription ID']
        );
    });
});
