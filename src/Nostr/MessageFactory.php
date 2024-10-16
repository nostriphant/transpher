<?php

namespace rikmeijer\Transpher\Nostr;
use function \Functional\map;

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
    
    static function privateDirect(Key $private_key, string $recipient_pubkey, string $message): Message {
        return new Message(['EVENT', get_object_vars(Event\Gift::wrap($recipient_pubkey, Event\Seal::close($private_key, $recipient_pubkey, new \rikmeijer\Transpher\Nostr\Rumor(
                                            pubkey: call_user_func($private_key, Key::public()),
                                            created_at: time(),
                                            kind: 14,
                                            content: $message,
                                            tags: [['p', $recipient_pubkey]]
                            ))))]);
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
    
    static function close(string $subscriptionId): Message {
        return new Message(['CLOSE', $subscriptionId]);
    }
    
    static function subscribe(Message\Subscribe\Filter ...$filters): Message {
        return new Message(array_merge(['REQ', bin2hex(random_bytes(32))], map($filters, fn(Message\Subscribe\Filter $filter) => $filter->conditions)));
    }
    
    static function filter(mixed ...$conditions) {
        return new Message\Subscribe\Filter(...$conditions);
    }
    
    static function requestedEvent(string $subscriptionId, Event $event): Message {
        return new Message(['EVENT', $subscriptionId, get_object_vars($event)]);
    }
}
