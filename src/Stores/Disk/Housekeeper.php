<?php

namespace nostriphant\Transpher\Stores\Disk;

use nostriphant\Transpher\Stores\Disk;
use nostriphant\NIP01\Event;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private Disk $store) {
        
    }

    public function __invoke(): void {
        Disk::walk_store($this->store->store, function (Event $event) {
            if (call_user_func($this->store->whitelist, $event) !== false) {
                return true;
            }

            unset($this->store[$event->id]);
            return false;
        });
    }
}
