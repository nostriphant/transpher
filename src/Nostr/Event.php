<?php

namespace nostriphant\Transpher\Nostr;

use nostriphant\Transpher\Nostr\Event\KindClass;
use function \Functional\select,
             \Functional\map;

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
            $event->kind === 1 => KindClass::REGULAR,
            $event->kind === 2 => KindClass::REGULAR,
            4 <= $event->kind && $event->kind < 45 => KindClass::REGULAR,
            1000 <= $event->kind && $event->kind < 10000 => KindClass::REGULAR,
            $event->kind == 0 => KindClass::REPLACEABLE,
            $event->kind === 3 => KindClass::REPLACEABLE,
            10000 <= $event->kind && $event->kind < 20000 => KindClass::REPLACEABLE,
            20000 <= $event->kind && $event->kind < 30000 => KindClass::EPHEMERAL,
            30000 <= $event->kind && $event->kind < 40000 => KindClass::ADDRESSABLE,
            default => KindClass::UNDEFINED
        };
    }

    static function hasTag(self $event, string $tag_identifier): bool {
        return count(array_filter($event->tags, fn(array $tag) => $tag[0] === $tag_identifier)) > 0;
    }

    static function extractTagValues(self $event, string $tag_identifier): array {
        return array_values(array_map(fn(array $tag) => $tag[1], array_filter($event->tags, fn(array $tag) => $tag[0] === $tag_identifier)));
    }

    static function verify(self $event): bool {
        return Key::verify($event->pubkey, $event->sig, $event->id);
    }

    public static function __set_state(array $properties) : self {
        return new Event(...$properties);
    }
}
