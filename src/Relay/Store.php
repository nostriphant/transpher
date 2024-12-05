<?php

namespace nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;
use nostriphant\Transpher\Stores\Results;

interface Store extends \ArrayAccess, \Countable {

    public function __invoke(Subscription $subscription): Results;

    #[\ReturnTypeWillChange]
    #[\Override]
    public function offsetGet(mixed $offset): ?Event;
}
