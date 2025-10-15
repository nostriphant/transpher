<?php

namespace nostriphant\Transpher\Nostr;

interface Transmission {
    
    public function __invoke(mixed $json) : bool;
    
}
