<?php

namespace nostriphant\Transpher\Stores;

class Results {

    public function __construct(public int $affected_rows = 0, private \Traversable $results = new \ArrayObject) {
        
    }

    public function __invoke(): \Generator {
        yield from $this->results;
    }
}
