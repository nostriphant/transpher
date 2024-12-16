<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\NIP01\Event;

trait MemoryWrapper {

    readonly private Memory $memory;

    public function __construct(array $events, array $whitelist_prototypes) {
        $this->memory = new Memory($events, $whitelist_prototypes);
    }

    public function offsetSet(mixed $offset, mixed $event): void {
        if (call_user_func($this->whitelist, $event) !== false) {
            $this->memory[$offset] = $event;
        }
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->memory[$offset]);
    }

    public function offsetExists(mixed $offset): bool {
        return isset($this->memory[$offset]);
    }

    public function offsetGet(mixed $offset): ?Event {
        return $this->memory[$offset];
    }

    public function __invoke(array ...$filter_prototypes): Results {
        return call_user_func_array($this->memory, $filter_prototypes);
    }

    public function count(): int {
        return count($this->memory);
    }

    public function current(): mixed {
        return current($this->memory);
    }

    public function key(): mixed {
        return key($this->memory);
    }

    public function next(): void {
        next($this->memory);
    }

    public function rewind(): void {
        reset($this->memory);
    }

    public function valid(): bool {
        return current($this->memory) !== false;
    }
}
