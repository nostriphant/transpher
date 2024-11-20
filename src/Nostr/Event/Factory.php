<?php

namespace nostriphant\Transpher\Nostr\Event;

use nostriphant\NIP01\Key;
use nostriphant\NIP01\Event;

class Factory {

    static function event(Key $sender_key, int $kind, string $content, array ...$tags): Event {
        return self::rumor($sender_key(Key::public()), time(), $kind, $content, ...$tags)($sender_key);
    }

    static function rumor(string $pubkey, int $created_at, int $kind, string $content, array ...$tags): \nostriphant\NIP59\Rumor {
        return new \nostriphant\NIP59\Rumor(
                pubkey: $pubkey,
                created_at: $created_at,
                kind: $kind,
                content: $content,
                tags: $tags
        );
    }
}
