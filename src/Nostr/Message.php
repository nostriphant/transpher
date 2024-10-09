<?php

namespace Transpher\Nostr;

use Transpher\Key;
use Transpher\Nostr\Rumor;
use Transpher\Nostr\Event;

/**
 * Class to contain Message related functions
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Message {
    
    static function rumor(int $kind, string $content, array ...$tags) : Message\Rumor {
        return new Message\Rumor(new Rumor(time(), $kind, $content, ...$tags));
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
    
    static function close(Message\Subscribe $subscription) : Message\Subscribe\Close {
        return new Message\Subscribe\Close($subscription()[1]);
    }
    
    static function subscribe() : Message\Subscribe {
        return new Message\Subscribe();
    }
    
    static function filter(Message\Subscribe\Chain $previous, mixed ...$conditions) {
        return new Message\Subscribe\Filter($previous, ...$conditions);
    }
    
    static function requestedEvent(string $subscriptionId, Event $event) {
        return ['EVENT', $subscriptionId, get_object_vars($event)];
    }
}
