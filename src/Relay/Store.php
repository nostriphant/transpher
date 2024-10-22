<?php

namespace rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Filters;

/**
 *
 * @author rmeijer
 */
interface Store extends \ArrayAccess, \Countable {

    public function __invoke(Filters $subscription): callable;
}
