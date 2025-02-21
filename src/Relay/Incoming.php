<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Message;

readonly class Incoming {

    private array $types_enabled;

    public function __construct(Incoming\Type ...$types_enabled) {
        $types = [];
        foreach ($types_enabled as $type_enabled) {
            $classname = get_class($type_enabled);
            $types[strtoupper(substr($classname, strrpos($classname, '\\') + 1))] = $type_enabled;
        }
        $this->types_enabled = $types;
    }

    public function __invoke(Message $message): \Traversable {
        yield from \nostriphant\FunctionalAlternate\Alternate::{$message->type}($message->payload)(
                        ...$this->types_enabled,
                        default: new Incoming\Unknown($message->type)
                );
    }
}
