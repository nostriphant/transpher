<?php

namespace nostriphant\Transpher\Nostr\Message;

use function \Functional\map;
use nostriphant\Transpher\Nostr\Message;
use nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Nostr\Key;
use nostriphant\Transpher\Nostr\Event\Gift;
use nostriphant\Transpher\Nostr\Event\Seal;
use nostriphant\Transpher\Nostr\Subscription\Filter;

class Factory {

    static function message(string $type, mixed ...$payload): Message {
        return new Message($type, ...$payload);
    }

    static function event(Key $sender_key, int $kind, string $content, array ...$tags): Message {
        return self::eventAt($sender_key, $kind, $content, time(), ...$tags);
    }

    static function eventAt(Key $sender_key, int $kind, string $content, int $at, array ...$tags): Message {
        return self::message('EVENT', get_object_vars((new \nostriphant\Transpher\Nostr\Rumor(
                                        pubkey: $sender_key(Key::public()),
                                        created_at: $at,
                                        kind: $kind,
                                        content: $content,
                                        tags: $tags
                                ))($sender_key)));
    }

    static function privateDirect(Key $private_key, string $recipient_pubkey, string $message): Message {
        return self::message('EVENT', get_object_vars(Gift::wrap($recipient_pubkey, Seal::close($private_key, $recipient_pubkey, new \nostriphant\Transpher\Nostr\Rumor(
                                                        pubkey: $private_key(Key::public()),
                                            created_at: time(),
                                            kind: 14,
                                            content: $message,
                                            tags: [['p', $recipient_pubkey]]
                            )))));
    }
    
    static function eose(string $subscriptionId): Message {
        return self::message('EOSE', $subscriptionId);
    }
    static function ok(string $eventId, bool $accepted, string $message = ''): Message {
        return self::message('OK', $eventId, $accepted, $message);
    }
    static function accept(string $eventId, string $message = ''): Message {
        return self::ok($eventId, true, $message);
    }
    static function notice(string $message): Message {
        return self::message('NOTICE', $message);
    }
    static function closed(string $subscriptionId, string $message = ''): Message {
        return self::message('CLOSED', $subscriptionId, $message);
    }
    
    static function close(string $subscriptionId): Message {
        return self::message('CLOSE', $subscriptionId);
    }
    
    static function subscribe(Filter ...$filters): Message {
        return self::message('REQ', bin2hex(random_bytes(32)), ...map($filters, fn(Filter $filter) => $filter->conditions));
    }

    static function requestedEvent(string $subscriptionId, Event $event): Message {
        return self::message('EVENT', $subscriptionId, get_object_vars($event));
    }
}
