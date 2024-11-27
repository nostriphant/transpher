<?php

namespace nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Subscription;

/**
 *
 * @author rmeijer
 */
interface Store extends \ArrayAccess, \Countable {

    public function __invoke(Subscription $subscription): \Generator;
}
