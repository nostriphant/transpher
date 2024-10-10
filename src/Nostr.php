<?php

namespace rikmeijer\Transpher;

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
    static function encrypt(Key $sender_key, string $recipient_pubkey) : Nostr\Encrypter {
        return new Nostr\Encrypter($sender_key, $recipient_pubkey);
    }
    static function decrypt(Key $recipient_key, string $sender_pubkey) : Nostr\Decrypter {
        return new Nostr\Decrypter($recipient_key, $sender_pubkey);
    }
    
}