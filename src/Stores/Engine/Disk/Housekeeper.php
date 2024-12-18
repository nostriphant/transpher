<?php

namespace nostriphant\Transpher\Stores\Engine\Disk;

use nostriphant\Transpher\Stores\Engine\Disk;
use nostriphant\NIP01\Event;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private Disk $store) {
        
    }

    public function __invoke(array $whitelist_prototypes): void {
        $whitelist = \nostriphant\Transpher\Relay\Condition::makeConditions($whitelist_prototypes);
        Disk::walk_store($this->store->store, function (Event $event) use ($whitelist) {
            if (call_user_func($whitelist, $event) !== false) {
                return true;
            }

            unset($this->store[$event->id]);
            return false;
        });
    }
}
