<?php

namespace Transpher\Nostr\Event;
use Transpher\Nostr;
use Transpher\Nostr\NIP44;
use Transpher\Key;
use Transpher\Nostr\Event;
use Transpher\Nostr\Rumor;

/**
 * Works with NIP-59 kind 1059 events
 * @see https://github.com/nostr-protocol/nips/blob/master/59.md
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Gift {
    
    static function wrap(string $recipient_pubkey, Event $event) : Event {
        $randomKey = Key::generate();
        $conversation_key = NIP44::getConversationKey($randomKey, hex2bin($recipient_pubkey));
        
        $gift = new Rumor(
            pubkey: $randomKey(Key::public()),
            created_at: mktime(rand(0,23), rand(0,59), rand(0,59)),
            kind: 1059, 
            content: NIP44::encrypt(Nostr::encode(get_object_vars($event)), $conversation_key, random_bytes(32)), 
            tags: [['p', $recipient_pubkey]]
        );
        return $gift($randomKey);
    }
    
    static function unwrap(Key $recipient_key, string $sender_pubkey, string $gift) : array {
        $seal_conversation_key = NIP44::getConversationKey($recipient_key, hex2bin($sender_pubkey));
        return Nostr::decode(NIP44::decrypt($gift, $seal_conversation_key), true);
    }
    
}
