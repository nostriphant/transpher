<?php

namespace nostriphant\Transpher\Relay\Incoming;

interface Type {

    public function __invoke(array $payload): \Generator;
}
