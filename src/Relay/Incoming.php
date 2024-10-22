<?php

namespace rikmeijer\Transpher\Relay;

/**
 *
 * @author rmeijer
 */
interface Incoming {

    public function __invoke(): \Generator;
}
