<?php

namespace nostriphant\TranspherTests;

use nostriphant\NIP01\Message;
use nostriphant\NIP01\Key;
use nostriphant\NIP59\Rumor;

class Factory {

    static function event(Key $sender_key, int $kind, string $content, array ...$tags): Message {
        return self::eventAt($sender_key, $kind, $content, time(), ...$tags);
    }

    static function eventAt(Key $sender_key, int $kind, string $content, int $at, array ...$tags): Message {
        return Message::event((new Rumor(
                                pubkey: $sender_key(Key::public()),
                                        created_at: $at,
                                        kind: $kind,
                                        content: $content,
                                        tags: $tags
                                ))($sender_key));
    }

    static function subscribe(array ...$filters): Message {
        return Message::req(bin2hex(random_bytes(32)), ...$filters);
    }
}
