<?php

namespace nostriphant\Transpher\Stores\Engine\Memory;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private \nostriphant\Transpher\Stores\Engine\Memory $store) {
        
    }

    public function __invoke(array $whitelist_prototypes): void {
        $whitelist = \nostriphant\Transpher\Relay\Condition::makeConditions($whitelist_prototypes);
        foreach ($this->store as $event) {
            if (call_user_func($whitelist, $event) === false) {
                unset($this->store[$event->id]);
            }
        }
    }
}
