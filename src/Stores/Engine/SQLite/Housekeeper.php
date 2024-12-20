<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private \nostriphant\Transpher\Stores\Engine\SQLite $store) {
        
    }

    public function __invoke(\nostriphant\Transpher\Stores\Conditions $whitelist_conditions): void {
        $select_statement = $this->store->query($whitelist_conditions, null, "event.id");
        $statement = Statement::nest("DELETE FROM event WHERE event.id NOT IN (", $select_statement, ") RETURNING *");
        $statement($this->store->database);
    }
}
