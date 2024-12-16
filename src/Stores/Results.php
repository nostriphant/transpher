<?php

namespace nostriphant\Transpher\Stores;

final readonly class Results implements \IteratorAggregate {

    private \Closure $results;

    public function __construct(?\Closure $results = null) {
        $this->results = $results ?? function () {
                    yield from [];
                };
    }

    #[\Override]
    public function getIterator(): \Traversable {
        return call_user_func($this->results);
    }
}
