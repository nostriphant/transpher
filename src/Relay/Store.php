<?php

namespace rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Filters;

/**
 *
 * @author rmeijer
 */
interface Store extends \ArrayAccess {

    public function __invoke(Filters $subscription): callable;
}
