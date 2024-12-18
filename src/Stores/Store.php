<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\NIP01\Event;

readonly class Store implements \ArrayAccess, \Countable, \IteratorAggregate {

    private \Closure $whitelist;

    public function __construct(private Engine $engine, array $whitelist_prototypes) {
        $disabled = array_reduce($whitelist_prototypes, fn(bool $disabled, array $filter_prototype) => empty($filter_prototype), empty($whitelist_prototypes));

        if ($disabled === false) {
            $this->engine::housekeeper($this->engine)($whitelist_prototypes);
            $this->whitelist = \nostriphant\Transpher\Relay\Condition::makeConditions($whitelist_prototypes);
        } else {
            $this->whitelist = fn() => true;
        }
    }

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
