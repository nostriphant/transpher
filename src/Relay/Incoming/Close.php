<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;

/**
 * Description of Close
 *
 * @author rmeijer
 */
readonly class Close implements Type {

    public function __construct(private \nostriphant\Transpher\Relay\Subscriptions $subscriptions) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        if (count($payload) < 1) {
            yield Factory::notice('Missing subscription ID');
        } else {
            ($this->subscriptions)($payload[0]);
            yield Factory::closed($payload[0]);
        }
    }
}
