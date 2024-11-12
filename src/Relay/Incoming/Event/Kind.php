<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

interface Kind {

    public function __construct(\nostriphant\Transpher\Relay\Store $store, string $files);

    static function validate(\nostriphant\Transpher\Nostr\Event $event): \nostriphant\Transpher\Relay\Incoming\Constraint;

    public function __invoke(\nostriphant\Transpher\Nostr\Event $event): void;
}
