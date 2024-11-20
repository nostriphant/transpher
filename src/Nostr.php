<?php

namespace nostriphant\Transpher;

class Nostr {
    
    static function encode(mixed $json) : string {
        return json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    static function decode(string $json): mixed {
        $object = json_decode($json, true);
        if (isset($object) === false) {
            throw new \InvalidArgumentException('Invalid message');
        }
        return $object;
    }
}