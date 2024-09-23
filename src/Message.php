<?php

namespace Transpher;

use Transpher\Nostr;
use function Functional\map;

/**
 * Class to contain Message related functions
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Message {
    
    static function event(int $kind, string $content, array ...$tags) {
        return \Functional\partial_right([Nostr::class, 'event'], time(), $kind, $tags, $content);
    }
    
    
    static function seal(callable $private_key, array $direct_message, string $recipient_pubkey) {
        $conversation_key = self::getConversationKey($recipient_pubkey);
        $encrypted_direct_message = Nostr\NIP44::encrypt(json_encode($direct_message), $private_key($conversation_key), random_bytes(32));
        return Nostr::event($private_key, mktime(rand(0,23), rand(0,59), rand(0,59)), 1059, [], $encrypted_direct_message);
    }
    
    static function getConversationKey(string $recipient_pubkey) : callable {
        return function(string $hex_private_key) use ($recipient_pubkey) : string {
            $key = Nostr\NIP44::getConversationKey(hex2bin($hex_private_key), hex2bin($recipient_pubkey));
            if ($key === false) {
                throw new \Exception('Unable to determine conversation key');
            }
            return $key;
        };
    }
    
    static function giftWrap(array $event, string $recipient_pubkey) {
        $randomKey = \Transpher\Key::generate();
        $conversation_key = self::getConversationKey($recipient_pubkey);
        $encrypted = Nostr\NIP44::encrypt(json_encode($event), $randomKey($conversation_key), random_bytes(32));
        return Nostr::event($randomKey, mktime(rand(0,23), rand(0,59), rand(0,59)), 1059, ['p', $recipient_pubkey], $encrypted);
    }
    
    static function privateDirect(callable $private_key, string $recipient_pubkey) {
        return function(string $message) use ($private_key, $recipient_pubkey) {
            $unsigned_event = Message::event(14, $message, ['p', $recipient_pubkey]);
            $direct_message = $unsigned_event($private_key);
            unset($direct_message[1]['sig']);
            return self::giftWrap(self::seal($private_key, $direct_message, $recipient_pubkey), $recipient_pubkey);
        };
    }
    
    static function close(callable $subscription) {
        return fn() => ['CLOSE', $subscription()[1]];
    }
    
    static function subscribe() {
        $subscriptionId = substr(uniqid().uniqid().uniqid().uniqid().uniqid().uniqid(), 0, 64);
        return fn() => ['REQ', $subscriptionId];
    }
    
    static function filter(callable $previous, mixed ...$conditions) {
        $filtered_conditions = array_filter($conditions, fn(string $key) => in_array($key, ["ids", "authors", "kinds", "tags", "since", "until", "limit"]), ARRAY_FILTER_USE_KEY);
        if (count($filtered_conditions) === 0) {
            return $previous;
        }
        if (array_key_exists('tags', $filtered_conditions)) {
            $tags = $filtered_conditions['tags'];
            unset($filtered_conditions['tags']);
            $filtered_conditions = array_merge($filtered_conditions, $tags);
        }
        return fn() => array_merge($previous(), [$filtered_conditions]);
    }
    
}
