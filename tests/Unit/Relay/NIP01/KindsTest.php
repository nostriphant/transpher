<?php

use nostriphant\Transpher\Nostr\Message\Factory;

describe('Kinds (https://nips.nostr.com/1#kinds)', function () {


    it('sends a notice for undefined event kinds', function () {
        $sender_key = \Pest\key_sender();
        $event = Factory::event($sender_key, -1, 'Hello World');
        $recipient = \Pest\handle($event);

        expect($recipient)->toHaveReceived(
                ['NOTICE', 'Undefined event kind -1']
        );
    });

});
