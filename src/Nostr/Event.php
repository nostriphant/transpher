<?php

namespace Transpher\Nostr;

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
    
    static function import(array $event) : self {
        return new self(...$event);
    }
    
    static function export(self $event) : array {
        return get_object_vars($event);
    }
    
    public static function __set_state(array $properties) : self {
        return self::import($properties);
    }
}
