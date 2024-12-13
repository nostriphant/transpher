<?php

namespace nostriphant\Transpher\Stores\SQLite;

class Housekeeper {
    public function __construct() {
        
    }

    public function __invoke(array $whitelist_prototypes): Statement {
        $select_statement = TransformSubscription::transformToSQL3StatementFactory(new \nostriphant\Transpher\Nostr\Subscription($whitelist_prototypes, Condition::class), "event.id");
        return Statement::nest("DELETE FROM event WHERE event.id NOT IN (", $select_statement, ") RETURNING *");
    }
}
