<?php

namespace rikmeijer\Transpher\Nostr;

use rikmeijer\Transpher\Nostr\Event\KindClass;

readonly class Event {

    public function __construct(
        public string $id, 
        public string $pubkey, 
        public int $created_at, 
        public int $kind, 
        public string $content, 
        public string $sig, 
        public array $tags
    ) {

    }

    static function determineClass(self $event): KindClass {
        return match (true) {
            1000 <= $event->kind && $event->kind < 10000 => KindClass::REGULAR,
            4 <= $event->kind && $event->kind < 45 => KindClass::REGULAR,
            $event->kind === 1 => KindClass::REGULAR,
            $event->kind === 2 => KindClass::REGULAR,
            10000 <= $event->kind && $event->kind < 20000 => KindClass::REPLACEABLE,
            $event->kind == 0 => KindClass::REPLACEABLE,
            $event->kind === 3 => KindClass::REPLACEABLE,
            20000 <= $event->kind && $event->kind < 30000 => KindClass::EPHEMERAL,
            30000 <= $event->kind && $event->kind < 40000 => KindClass::ADDRESSABLE,
            default => KindClass::UNDEFINED
        };
    }

    public static function __set_state(array $properties) : self {
        return new Event(...$properties);
    }
}
