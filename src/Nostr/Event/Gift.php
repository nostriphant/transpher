<?php

namespace nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Nostr;
use nostriphant\Transpher\Nostr\Key;
use nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Nostr\Rumor;

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
            created_at: time() - rand(0, 60 * 60 * 48),
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
