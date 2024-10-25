<?php

use rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Key;
use rikmeijer\Transpher\Nostr\Message\Factory;
use function Pest\context;

describe('Kinds (https://nips.nostr.com/1#kinds)', function () {


    it('sends a notice for undefined event kinds', function () {
        $context = context();

        $sender_key = Key::generate();
        $event = Factory::event($sender_key, -1, 'Hello World');
        Relay::handle($event, $context);

        expect($context->relay)->toHaveReceived(
                ['NOTICE', 'Undefined event kind -1']
        );
    });

});
