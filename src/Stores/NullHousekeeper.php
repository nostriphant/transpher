<?php

namespace nostriphant\Transpher\Stores;

class NullHousekeeper implements Housekeeper {

    public function __invoke(array $whitelist_prototypes): void {
        
    }
}
