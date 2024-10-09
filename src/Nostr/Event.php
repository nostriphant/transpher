<?php

namespace rikmeijer\Transpher\Nostr;

/**
 * Description of Event
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
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
    
    public static function __set_state(array $properties) : self {
        return new Event(...$properties);
    }
}
