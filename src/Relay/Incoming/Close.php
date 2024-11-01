<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;

/**
 * Description of Close
 *
 * @author rmeijer
 */
readonly class Close implements Type {

    private string $subscription_id;

    public function __construct(private \nostriphant\Transpher\Relay\Subscriptions $subscriptions, array $message) {
        if (count($message) < 2) {
            throw new \InvalidArgumentException('Missing subscription ID');
        }

        $this->subscription_id = $message[1];
    }

    #[\Override]
    public function __invoke(): \Generator {
        ($this->subscriptions)($this->subscription_id);
        yield Factory::closed($this->subscription_id);
    }
}
