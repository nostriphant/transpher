<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;

/**
 * Description of Close
 *
 * @author rmeijer
 */
readonly class Close {
    private string $subscription_id;

    public function __construct(array $message) {
        if (count($message) < 2) {
            throw new \InvalidArgumentException('Missing subscription ID');
        }

        $this->subscription_id = $message[1];
    }

    public function __invoke(Context $context): \Generator {
        ($context->subscriptions)($this->subscription_id);
        yield Factory::closed($this->subscription_id);
    }
}
