<?php

namespace nostriphant\Transpher\Nostr;

use nostriphant\FunctionalAlternate\Alternate;
use nostriphant\NIP01\Key;

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

    static function alternateClass(self $event): Alternate {
        return match (true) {
            $event->kind === 1 => Alternate::regular($event),
            $event->kind === 2 => Alternate::regular($event),
            4 <= $event->kind && $event->kind < 45 => Alternate::regular($event),
            1000 <= $event->kind && $event->kind < 10000 => Alternate::regular($event),
            $event->kind == 0 => Alternate::replaceable($event),
            $event->kind === 3 => Alternate::replaceable($event),
            10000 <= $event->kind && $event->kind < 20000 => Alternate::replaceable($event),
            20000 <= $event->kind && $event->kind < 30000 => Alternate::ephemeral($event),
            30000 <= $event->kind && $event->kind < 40000 => Alternate::addressable($event),
            default => Alternate::undefined($event),
        };
    }

    static function hasTag(self $event, string $tag_identifier): bool {
        return count(array_filter($event->tags, fn(array $tag) => $tag[0] === $tag_identifier)) > 0;
    }

    static function extractTagValues(self $event, string $tag_identifier): array {
        return array_values(array_map(fn(array $tag) => array_slice($tag, 1), array_filter($event->tags, fn(array $tag) => $tag[0] === $tag_identifier)));
    }

    static function verify(self $event): bool {
        return Key::verify($event->pubkey, $event->sig, $event->id);
    }

    public static function __set_state(array $properties) : self {
        return new self(...$properties);
    }
}
