<?php

namespace rikmeijer\Transpher;
use rikmeijer\Transpher\Nostr\NIP44;

/**
 * Description of Nostr
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Nostr {
    
    static function encode(mixed $json) : string {
        return json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    static function decode(string $json) : mixed {
        return json_decode($json, true);
    }
    static function encrypt(Key $sender_key, string $recipient_pubkey) : callable {
        $conversation_key = NIP44::getConversationKey($sender_key, $recipient_pubkey);
        return function(string $message) use ($conversation_key) : string {
            return NIP44::encrypt($message, $conversation_key, random_bytes(32));
        };
    }
    static function decrypt(Key $recipient_key, string $sender_pubkey) : callable {
        $conversation_key = NIP44::getConversationKey($recipient_key, $sender_pubkey);
        return \Functional\partial_right([NIP44::class, 'decrypt'], $conversation_key);
    }
    
}