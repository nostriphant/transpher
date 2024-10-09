<?php

namespace Transpher\Nostr;

use Transpher\Nostr;
use Transpher\Key;
use Transpher\Nostr\Event;
use Transpher\Nostr\Event\Gift;
use Transpher\Nostr\Event\Seal;
use function Functional\map;
use Transpher\Nostr\Event\Signed;

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
    
    static function close(Message\Subscribe $subscription) : Message\Subscribe\Close {
        return new Message\Subscribe\Close($subscription()[1]);
    }
    
    static function subscribe() : Message\Subscribe {
        return new Message\Subscribe();
    }
    
    static function filter(Message\Subscribe\Chain $previous, mixed ...$conditions) {
        return new Message\Subscribe\Filter($previous, ...$conditions);
    }
    
    
    static function requestedEvent(string $subscriptionId, Signed $event) {
        return ['EVENT', $subscriptionId, Signed::export($event)];
    }
}
