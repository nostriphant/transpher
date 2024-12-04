<?php

namespace nostriphant\Transpher\Stores\SQLite;

use nostriphant\Transpher\Nostr\Subscription;

class Housekeeper {
    public function __construct(private \Psr\Log\LoggerInterface $log) {
        
    }

    public function __invoke(\SQLite3 $database, Subscription $whitelist): void {
        if ($whitelist->enabled === false) {
            return;
        }
        $this->log->info('Whitelist enabled, clearing up database...');
        $select_statement = TransformSubscription::transformToSQL3StatementFactory($whitelist, "event.id");
        $statement = Statement::nest("DELETE FROM event WHERE event.id NOT IN (", $select_statement, ") ");
        $affected_rows = $statement($database, $this->log);
        $this->log->info('Cleanup succesful (' . $affected_rows->getReturn() . ')');
    }
}
