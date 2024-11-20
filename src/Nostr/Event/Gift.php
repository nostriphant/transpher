<?php

namespace nostriphant\Transpher\Nostr\Event;
use nostriphant\NIP01\Nostr;
use nostriphant\NIP01\Key;
use nostriphant\NIP01\Event;
use nostriphant\Transpher\Nostr\Rumor;
use nostriphant\NIP44\Encrypt,
    nostriphant\NIP44\Decrypt;

/**
 * Works with NIP-59 kind 1059 events
 * @see https://github.com/nostr-protocol/nips/blob/master/59.md
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Gift {
    
    static function wrap(string $recipient_pubkey, Event $event) : Event {
        $randomKey = Key::generate();
        $encrypter = Encrypt::make($randomKey, $recipient_pubkey);
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
        $decrypter = Decrypt::make($recipient_key, $sender_pubkey);
        return Nostr::decode($decrypter($gift), true);
    }
    
}
