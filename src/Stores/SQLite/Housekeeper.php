<?php

namespace nostriphant\Transpher\Stores\SQLite;

class Housekeeper {
    public function __construct() {
        
    }

    public function __invoke(\SQLite3 $database, array $whitelist_prototypes): void {
        if (count(array_filter($whitelist_prototypes)) === 0) {
            return;
        }
        $select_statement = TransformSubscription::transformToSQL3StatementFactory(new \nostriphant\Transpher\Nostr\Subscription($whitelist_prototypes, Condition::class), "event.id");
        $statement = Statement::nest("DELETE FROM event WHERE event.id NOT IN (", $select_statement, ") RETURNING *");
        $statement($database);
    }
}
