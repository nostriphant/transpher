<?php

namespace nostriphant\Transpher\Relay;

interface Sender {
    
    public function __invoke(mixed $json) : bool;
    
}
