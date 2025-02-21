<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Message;

readonly class Incoming {

    private array $types_enabled;

    public function __construct(Incoming\Type ...$types_enabled) {
        $this->types_enabled = $types_enabled;
    }

    static function withType(self $incoming, Incoming\Type $type): self {
        $types = $incoming->types_enabled;
        $types[] = $type;
        return new self(...$types);
    }

    public function __invoke(Message $message): \Traversable {
        $types = [];
        foreach ($this->types_enabled as $type_enabled) {
            $classname = get_class($type_enabled);
            $types[strtoupper(substr($classname, strrpos($classname, '\\') + 1))] = $type_enabled;
        }

        yield from \nostriphant\FunctionalAlternate\Alternate::{$message->type}($message->payload)(
                        ...$types,
                        default: new Incoming\Unknown($message->type)
                );
    }
}
