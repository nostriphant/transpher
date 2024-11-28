<?php

namespace nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;

interface Store extends \ArrayAccess, \Countable {

    public function __invoke(Subscription $subscription): \Generator;

    #[\ReturnTypeWillChange]
    #[\Override]
    public function offsetGet(mixed $offset): Event;
}
