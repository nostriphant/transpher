<?php

use nostriphant\NIP01Tests\Functions as NIP01TestFunctions;
use nostriphant\TranspherTests\Factory;

describe('Kinds (https://nips.nostr.com/1#kinds)', function () {


    it('sends a notice for undefined event kinds', function () {
        $sender_key = NIP01TestFunctions::key_sender();
        $event = Factory::event($sender_key, -1, 'Hello World');
        $recipient = \Pest\handle($event);

        expect($recipient)->toHaveReceived(
                ['NOTICE', 'Undefined event kind -1']
        );
    });

});
