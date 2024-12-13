<?php

namespace nostriphant\Transpher\Stores;

class NullHousekeeper implements Housekeeper {

    public function __invoke(): void {
        
    }
}
