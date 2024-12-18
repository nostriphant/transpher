<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Relay\Conditions;

interface Housekeeper {
    public function __invoke(Conditions $whitelist_conditions): void;
}
