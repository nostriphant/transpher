<?php

namespace nostriphant\Transpher\Stores\SQLite;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private \nostriphant\Transpher\Stores\SQLite $store) {
        
    }

    public function __invoke(): void {
        $select_statement = TransformSubscription::transformToSQL3StatementFactory(new \nostriphant\Transpher\Nostr\Subscription($this->store->whitelist_prototypes, Condition::class), "event.id");
        $statement = Statement::nest("DELETE FROM event WHERE event.id NOT IN (", $select_statement, ") RETURNING *");
        $statement($this->store->database);
    }
}
