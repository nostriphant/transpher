<?php

namespace nostriphant\Transpher\Stores\Engine\Memory;

use nostriphant\Transpher\Nostr\Subscription;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private \nostriphant\Transpher\Stores\Engine\Memory $store) {
        
    }

    public function __invoke(array $whitelist_prototypes): void {
        $whitelist = new Subscription($whitelist_prototypes, \nostriphant\Transpher\Relay\Condition::class);
        foreach ($this->store as $event) {
            if (call_user_func($whitelist, $event) === false) {
                unset($this->store[$event->id]);
            }
        }
    }
}
