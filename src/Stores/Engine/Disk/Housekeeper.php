<?php

namespace nostriphant\Transpher\Stores\Engine\Disk;

use nostriphant\Transpher\Stores\Engine\Disk;
use nostriphant\NIP01\Event;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private Disk $store) {
        
    }

    public function __invoke(\nostriphant\Transpher\Stores\Conditions $whitelist_conditions): void {
        $whitelist = \nostriphant\Transpher\Stores\Engine\Memory\Condition::makeConditions($whitelist_conditions);
        Disk::walk_store($this->store->store, function (Event $event) use ($whitelist) {
            if (call_user_func($whitelist, $event) !== false) {
                return true;
            }

            unset($this->store[$event->id]);
            return false;
        });
    }
}
