<?php

namespace nostriphant\Transpher\Nostr;

interface Transmission {
    
    public function __invoke(\nostriphant\NIP01\Message $message) : bool;
    
}
