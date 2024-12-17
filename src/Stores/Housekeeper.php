<?php

namespace nostriphant\Transpher\Stores;

interface Housekeeper {
    public function __invoke(array $whitelist_prototypes): void;
}
