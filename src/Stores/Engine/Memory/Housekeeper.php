<?php

namespace nostriphant\Transpher\Stores\Engine\Memory;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private \nostriphant\Transpher\Stores\Engine\Memory $store) {
        
    }

    public function __invoke(\nostriphant\Transpher\Stores\Conditions $whitelist_conditions): void {
        $whitelist = Condition::makeConditions($whitelist_conditions);
        foreach ($this->store as $event) {
            if (call_user_func($whitelist, $event) === false) {
                unset($this->store[$event->id]);
            }
        }
    }
}
