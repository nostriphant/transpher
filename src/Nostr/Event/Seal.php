<?php

namespace Transpher\Nostr\Event;
use Transpher\Nostr;
use Transpher\Nostr\NIP44;
use Transpher\Key;

/**
 * Works with NIP-59 kind 13 events
 * @see https://github.com/nostr-protocol/nips/blob/master/59.md
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Seal {
    
    static function close(Key $sender_private_key, string $recipient_pubkey, array $event) : array {
        $conversation_key = NIP44::getConversationKey($sender_private_key, hex2bin($recipient_pubkey));
        $encrypted_direct_message = NIP44::encrypt(Nostr::encode($event), $conversation_key, random_bytes(32));
        
        $seal = new Nostr\Event(mktime(rand(0,23), rand(0,59), rand(0,59)), 13, $encrypted_direct_message, []);
        return $seal($sender_private_key);
    }
    
    static function open(Key $recipient_private_key, string $sender_pubkey, string $seal) : array {
        $pdm_conversation_key = NIP44::getConversationKey($recipient_private_key, hex2bin($sender_pubkey));
        return Nostr::decode(NIP44::decrypt($seal, $pdm_conversation_key));
    }
}
