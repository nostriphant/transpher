<?php

namespace Transpher\Nostr;

use Transpher\Nostr;
use Transpher\Key;
use Transpher\Nostr\Event;
use Transpher\Nostr\Event\Gift;
use Transpher\Nostr\Event\Seal;
use function Functional\map;

/**
 * Class to contain Message related functions
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Message {
    
    static function event(int $kind, string $content, array ...$tags) : Message\Event {
        return new Message\Event(new Event(time(), $kind, $content, ...$tags));
    }
    
    static function privateDirect(Key $private_key) : Message\PrivateDirect {
        return new Message\PrivateDirect($private_key);
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
    static function notice(string $message) : array {
        return ['NOTICE', $message];
    }
    static function closed(string $subscriptionId, string $message = '') : array {
        return ['CLOSED', $subscriptionId, $message];
    }
    
    static function close(callable $subscription) : callable {
        return fn() => ['CLOSE', $subscription()[1]];
    }
    
    static function subscribe() : Message\Subscribe {
        return new Message\Subscribe();
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
    
    
    static function requestedEvent(string $subscriptionId, array $event) {
        return ['EVENT', $subscriptionId, $event];
    }
}
