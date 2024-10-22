<?php

namespace rikmeijer\Transpher\Relay;

/**
 *
 * @author rmeijer
 */
interface Incoming {
    static function fromMessage(array $message): self;

    public function __invoke(Incoming\Context $context): \Generator;
}
