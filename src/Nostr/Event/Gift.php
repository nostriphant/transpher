<?php

namespace Transpher\Nostr\Event;
use Transpher\Nostr;
use Transpher\Nostr\NIP44;
use Transpher\Key;

/**
 * Works with NIP-59 kind 1059 events
 * @see https://github.com/nostr-protocol/nips/blob/master/59.md
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Gift {
    
    static function wrap(string $recipient_pubkey, array $event) : array {
        $randomKey = Key::generate();
        $conversation_key = NIP44::getConversationKey($randomKey, hex2bin($recipient_pubkey));
        $encrypted = NIP44::encrypt(Nostr::encode($event), $conversation_key, random_bytes(32));
        
        $gift = new Nostr\Rumor(mktime(rand(0,23), rand(0,59), rand(0,59)), 1059, $encrypted, ['p', $recipient_pubkey]);
        return $gift($randomKey);
    }
    
    static function unwrap(Key $recipient_key, string $sender_pubkey, string $gift) : array {
        $seal_conversation_key = NIP44::getConversationKey($recipient_key, hex2bin($sender_pubkey));
        return Nostr::decode(NIP44::decrypt($gift, $seal_conversation_key), true);
    }
    
}
