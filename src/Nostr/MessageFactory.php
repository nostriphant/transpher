<?php

namespace rikmeijer\Transpher\Nostr;

/**
 * Class to contain Message related functions
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class MessageFactory {

    static function rumor(string $sender_pubkey, int $kind, string $content, array ...$tags) : Message\Rumor {
        return new Message\Rumor(new Rumor(
            pubkey: $sender_pubkey,
            created_at: time(), 
            kind: $kind, 
            content: $content,
            tags: $tags
        ));
    }
    
    static function privateDirect(Key $private_key) : Message\PrivateDirect {
        return new Message\PrivateDirect($private_key);
    }
    
    static function eose(string $subscriptionId): Message {
        return new Message(['EOSE', $subscriptionId]);
    }
    static function ok(string $eventId, bool $accepted, string $message = ''): Message {
        return new Message(['OK', $eventId, $accepted, $message]);
    }
    static function accept(string $eventId, string $message = ''): Message {
        return self::ok($eventId, true, $message);
    }
    static function notice(string $message): Message {
        return new Message(['NOTICE', $message]);
    }
    static function closed(string $subscriptionId, string $message = ''): Message {
        return new Message(['CLOSED', $subscriptionId, $message]);
    }
    
    static function close(Message\Subscribe $subscription): Message {
        return new Message(['CLOSE', $subscription()[1]]);
    }
    
    static function subscribe() : Message\Subscribe {
        return new Message\Subscribe();
    }
    
    static function filter(Message\Subscribe\Chain $previous, mixed ...$conditions) {
        return new Message\Subscribe\Filter($previous, ...$conditions);
    }
    
    static function requestedEvent(string $subscriptionId, Event $event): Message {
        return new Message(['EVENT', $subscriptionId, get_object_vars($event)]);
    }
}
