<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\NIP01\Event;

readonly class Store implements \ArrayAccess, \Countable, \IteratorAggregate {

    private \Closure $whitelist;

    public function __construct(private Engine $engine, array $whitelist_prototypes) {
        $disabled = array_reduce($whitelist_prototypes, fn(bool $disabled, array $filter_prototype) => empty($filter_prototype), empty($whitelist_prototypes));

        if ($disabled === false) {
            $conditions = new Conditions($whitelist_prototypes);
            $this->engine::housekeeper($this->engine)($conditions);
            $this->whitelist = Engine\Memory\Condition::makeConditions($conditions);
        } else {
            $this->whitelist = fn() => true;
        }
    }

    static function query(Engine $engine, array ...$filter_prototypes): Results {
        $limit = array_reduce($filter_prototypes, fn(?int $limit, array $filter_prototype) => $filter_prototype['limit'] ?? $limit, null);
        return call_user_func($engine, new Conditions($filter_prototypes), $limit);
    }

    public function __invoke(array ...$filter_prototypes): Results {
        return self::query($this->engine, ...$filter_prototypes);
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
        return iterator_count(self::query($this->engine, []));
    }

    #[\Override]
    public function getIterator(): \Traversable {
        return self::query($this->engine, []);
    }
}
