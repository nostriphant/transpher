<?php

namespace nostriphant\Transpher\Stores;

interface Housekeeper {
    public function __invoke(Conditions $whitelist_conditions): void;
}
