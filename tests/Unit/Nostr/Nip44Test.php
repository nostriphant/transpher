<?php

use nostriphant\NIP01\Key;
use nostriphant\Transpher\Nostr;

describe('NIP-44 v2', function () {
    it('Nostr class wraps around NIP44 implementation', function() {
        $recipient_key = \Pest\key_recipient();
        $sender_key = \Pest\key_sender();

        $encrypter = Nostr::encrypt($sender_key, hex2bin($recipient_key(Key::public())));
        $encrypted = $encrypter('Hello World!');
        
        $decrypter = Nostr::decrypt($recipient_key, hex2bin($sender_key(Key::public())));
        expect($decrypter($encrypted))->toBe('Hello World!');
    });
});

