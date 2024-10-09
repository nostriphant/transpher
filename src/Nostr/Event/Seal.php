<?php

namespace Transpher\Nostr\Event;
use Transpher\Nostr;
use Transpher\Nostr\NIP44;
use Transpher\Key;
use Transpher\Nostr\Rumor;
use Transpher\Nostr\Event;

/**
 * Works with NIP-59 kind 13 events
 * @see https://github.com/nostr-protocol/nips/blob/master/59.md
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Seal {
    
    static function close(Key $sender_private_key, string $recipient_pubkey, Rumor $event) : Event {
        $conversation_key = NIP44::getConversationKey($sender_private_key, hex2bin($recipient_pubkey));
        
        $seal = new Rumor(
            pubkey: $sender_private_key(Key::public()),
            created_at: mktime(rand(0,23), rand(0,59), rand(0,59)), 
            kind: 13, 
            content: NIP44::encrypt(Nostr::encode(get_object_vars($event)), $conversation_key, random_bytes(32)), 
            tags: []
        );
        return $seal($sender_private_key);
    }
    
    static function open(Key $recipient_private_key, string $sender_pubkey, string $seal) : array {
        $pdm_conversation_key = NIP44::getConversationKey($recipient_private_key, hex2bin($sender_pubkey));
        return Nostr::decode(NIP44::decrypt($seal, $pdm_conversation_key));
    }
}
