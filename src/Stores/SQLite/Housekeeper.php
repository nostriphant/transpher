<?php

namespace nostriphant\Transpher\Stores\SQLite;

use nostriphant\Transpher\Nostr\Subscription;

class Housekeeper {
    public function __construct() {
        
    }

    public function __invoke(\SQLite3 $database, Subscription $whitelist): void {
        if ($whitelist->enabled === false) {
            return;
        }
        $select_statement = TransformSubscription::transformToSQL3StatementFactory($whitelist, "event.id");
        $statement = Statement::nest("DELETE FROM event WHERE event.id NOT IN (", $select_statement, ")");
        $statement($database)->getReturn();
    }
}
