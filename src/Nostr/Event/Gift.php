<?php

namespace rikmeijer\Transpher\Nostr\Event;
use rikmeijer\Transpher\Nostr;
use rikmeijer\Transpher\Nostr\Key;
use rikmeijer\Transpher\Nostr\Event;
use rikmeijer\Transpher\Nostr\Rumor;

/**
 * Works with NIP-59 kind 1059 events
 * @see https://github.com/nostr-protocol/nips/blob/master/59.md
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Gift {
    
    static function wrap(string $recipient_pubkey, Event $event) : Event {
        $randomKey = Key::generate();
        $encrypter = Nostr::encrypt($randomKey, hex2bin($recipient_pubkey));
        $gift = new Rumor(
            pubkey: $randomKey(Key::public()),
            created_at: mktime(rand(0,23), rand(0,59), rand(0,59)),
            kind: 1059, 
            content: $encrypter(Nostr::encode(get_object_vars($event))), 
            tags: [['p', $recipient_pubkey]]
        );
        return $gift($randomKey);
    }
    
    static function unwrap(Key $recipient_key, string $sender_pubkey, string $gift) : array {
        $decrypter = Nostr::decrypt($recipient_key, hex2bin($sender_pubkey));
        return Nostr::decode($decrypter($gift), true);
    }
    
}
