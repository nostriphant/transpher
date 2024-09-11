<?php

namespace Transpher;

use Transpher\Nostr;

/**
 * Class to contain Message related functions
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Message {
    
    /**
     * Generate public key from private key as hex.
     *
     * @param string $private_hex
     *
     * @return string
     */
    static function getPublicFromPrivateKey(string $private_hex): string
    {
        $ec = new \Elliptic\EC('secp256k1');
        $private_key = $ec->keyFromPrivate($private_hex);
        $public_hex = $private_key->getPublic(true, 'hex');

        // remove compression prefix 02 | 03
        return substr($public_hex, 2);
    }
    
    static function event(int $kind, string $content, array ...$tags) {
        return \Functional\partial_right([Nostr::class, 'event'], time(), $kind, $tags, $content);
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
