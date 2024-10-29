<?php

namespace nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Filters;

/**
 *
 * @author rmeijer
 */
interface Store extends \ArrayAccess, \Countable {

    public function __invoke(Filters $subscription): array;
}
