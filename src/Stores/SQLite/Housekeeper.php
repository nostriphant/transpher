<?php

namespace nostriphant\Transpher\Stores\SQLite;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private \nostriphant\Transpher\Stores\SQLite $store) {
        
    }

    public function __invoke(array $whitelist_prototypes): void {
        $select_statement = $this->store->query(new \nostriphant\Transpher\Nostr\Subscription($whitelist_prototypes, Condition::class), "event.id");
        $statement = Statement::nest("DELETE FROM event WHERE event.id NOT IN (", $select_statement, ") RETURNING *");
        $statement($this->store->database);
    }
}
