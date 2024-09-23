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
    
    static function eose(string $subscriptionId) : array {
        return ['EOSE', $subscriptionId];
    }
    static function ok(string $eventId, bool $accepted, string $message = '') : array {
        return ['OK', $eventId, $accepted, $message];
    }
    static function accept(string $eventId, string $message = '') : array {
        return self::ok($eventId, true, $message);
    }
    static function closed(string $subscriptionId, string $message = '') : array {
        return ['CLOSED', $subscriptionId, $message];
    }
    static function notice(string $message) : array {
        return ['NOTICE', $message];
    }
    static function event(callable $private_key, int $created_at, int $kind, array $tags, string $content) : array {
        $id = hash('sha256', self::encode([0, $private_key(), $created_at, $kind, $tags, $content]));
        return ['EVENT', [
            "id" => $id,
            "pubkey" => $private_key(),
            "created_at" => $created_at,
            "kind" => $kind,
            "tags" => $tags,
            "content" => $content,
            "sig" => $private_key(\Transpher\Key::signer($id))
        ]];
    }
    static function subscribedEvent(string $subscriptionId, array $event) {
        return ['EVENT', $subscriptionId, $event];
    }
}