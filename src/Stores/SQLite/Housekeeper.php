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
        $factory = TransformSubscription::transformToSQL3StatementFactory($whitelist, "event.id");
        $statement = $database->prepare("DELETE "
                . "FROM event "
                . "WHERE event.id NOT IN (" . $factory($database, $this->log) . ") ");
        if ($statement === false) {
            $this->log->error('Query failed: ' . $database->lastErrorMsg());
            return;
        }
        $result = $statement->execute();
        $result->finalize();

        if ($result === false) {
            $this->log->error('Cleanup query failed: ' . $database->lastErrorMsg());
        } else {
            $this->log->info('Cleanup succesful (' . $database->changes() . ')');
        }
    }
}
