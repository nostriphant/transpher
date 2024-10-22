<?php

namespace rikmeijer\Transpher\Relay;

/**
 *
 * @author rmeijer
 */
interface Incoming {
    static function fromMessage(array $message): callable;

    public function __invoke(): \Generator;
}
