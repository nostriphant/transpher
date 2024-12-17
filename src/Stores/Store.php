<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;

readonly class Store implements \nostriphant\Transpher\Relay\Store {

    private \nostriphant\Transpher\Nostr\Subscription $whitelist;

    public function __construct(private \nostriphant\Transpher\Relay\Store $engine, private array $whitelist_prototypes) {
        \nostriphant\Transpher\Stores\do_housekeeping($this->engine, $whitelist_prototypes);
        $this->whitelist = new Subscription($this->whitelist_prototypes, \nostriphant\Transpher\Relay\Condition::class);
    }

    #[\Override]
    public function __invoke(array ...$filter_prototypes): Results {
        return call_user_func_array($this->engine, $filter_prototypes);
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        return isset($this->engine[$offset]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): ?Event {
        return $this->engine[$offset];
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void {
        if (call_user_func($this->whitelist, $value) === false) {
            return;
        } elseif (isset($offset)) {
            $this->engine[$offset] = $value;
        } else {
            $this->engine[] = $value;
        }
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        unset($this->engine[$offset]);
    }

    #[\Override]
    public function count(): int {
        return count($this->engine);
    }

    #[\Override]
    public function getIterator(): \Traversable {
        yield from $this->engine;
    }
}
