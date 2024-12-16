<?php

namespace nostriphant\Transpher\Nostr\Message;

use nostriphant\NIP01\Message;
use nostriphant\NIP01\Key;
use nostriphant\NIP59\Gift;
use nostriphant\NIP59\Seal;
use nostriphant\NIP59\Rumor;

class Factory {

    static function event(Key $sender_key, int $kind, string $content, array ...$tags): Message {
        return self::eventAt($sender_key, $kind, $content, time(), ...$tags);
    }

    static function eventAt(Key $sender_key, int $kind, string $content, int $at, array ...$tags): Message {
        return Message::event(get_object_vars((new Rumor(
                                        pubkey: $sender_key(Key::public()),
                                        created_at: $at,
                                        kind: $kind,
                                        content: $content,
                                        tags: $tags
                                ))($sender_key)));
    }

    static function privateDirect(Key $private_key, string $recipient_pubkey, string $message): Message {
        return Message::event(get_object_vars(Gift::wrap($recipient_pubkey, Seal::close($private_key, $recipient_pubkey, new Rumor(
                                                        pubkey: $private_key(Key::public()),
                                                        created_at: time(),
                                            kind: 14,
                                            content: $message,
                                            tags: [['p', $recipient_pubkey]]
                                        )))));
    }

    static function subscribe(array ...$filters): Message {
        return Message::req(bin2hex(random_bytes(32)), ...$filters);
    }
}
