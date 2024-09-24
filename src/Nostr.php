<?php

namespace Transpher;

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
}