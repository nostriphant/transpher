<?php

namespace nostriphant\Transpher\Stores\SQLite;

class Results {

    public function __construct(public int $affected_rows = 0, private \Iterator $results = new \ArrayIterator) {
        
    }

    public function __invoke(): \Generator {
        yield from $this->results;
    }

    static function fromSQLite3Result(\SQLite3Result $result, callable $mapper): self {
        return new self(results: call_user_func(function (\SQLite3Result $result) use ($mapper) {
                    while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
                        yield $mapper($data);
                    }
                }, $result));
    }
}
