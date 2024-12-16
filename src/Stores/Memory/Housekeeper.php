<?php

namespace nostriphant\Transpher\Stores\Memory;

readonly class Housekeeper implements \nostriphant\Transpher\Stores\Housekeeper {

    public function __construct(private \nostriphant\Transpher\Stores\Memory $store) {
        
    }

    public function __invoke(): void {
        foreach ($this->store as $event) {
            if (call_user_func($this->store->whitelist, $event) === false) {
                unset($this->store[$event->id]);
            }
        }
    }
}
