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
    public function __invoke(array $message): \Generator {
        if (count($message) < 2) {
            throw new \InvalidArgumentException('Missing subscription ID');
        }

        $subscription_id = $message[1];
        ($this->subscriptions)($subscription_id);
        yield Factory::closed($subscription_id);
    }
}
