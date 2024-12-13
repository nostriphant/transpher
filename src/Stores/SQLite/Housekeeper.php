<?php

namespace nostriphant\Transpher\Stores\SQLite;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private \SQLite3 $database, private array $whitelist_prototypes) {
        
    }

    public function __invoke(): void {
        $select_statement = TransformSubscription::transformToSQL3StatementFactory(new \nostriphant\Transpher\Nostr\Subscription($this->whitelist_prototypes, Condition::class), "event.id");
        $statement = Statement::nest("DELETE FROM event WHERE event.id NOT IN (", $select_statement, ") RETURNING *");
        $statement($this->database);
    }
}
