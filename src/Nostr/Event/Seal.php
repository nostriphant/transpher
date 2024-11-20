<?php

namespace nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Nostr;
use nostriphant\NIP01\Key;
use nostriphant\Transpher\Nostr\Rumor;
use nostriphant\NIP01\Event;
use nostriphant\NIP44\Encrypt,
    nostriphant\NIP44\Decrypt;

/**
 * Works with NIP-59 kind 13 events
 * @see https://github.com/nostr-protocol/nips/blob/master/59.md
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Seal {
    
    static function close(Key $sender_private_key, string $recipient_pubkey, Rumor $event) : Event {
        $encrypter = Encrypt::make($sender_private_key, $recipient_pubkey);
        $seal = new Rumor(
            pubkey: $sender_private_key(Key::public()),
            created_at: mktime(rand(0,23), rand(0,59), rand(0,59)), 
            kind: 13, 
            content: $encrypter(Nostr::encode(get_object_vars($event))), 
            tags: []
        );
        return $seal($sender_private_key);
    }
    
    static function open(Key $recipient_private_key, string $sender_pubkey, string $seal) : array {
        $decrypter = Decrypt::make($recipient_private_key, $sender_pubkey);
        return Nostr::decode($decrypter($seal));
    }
}
