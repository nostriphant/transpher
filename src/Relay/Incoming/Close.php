<?php

namespace rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Nostr\Message\Factory;

/**
 * Description of Close
 *
 * @author rmeijer
 */
readonly class Close implements Incoming {

    public function __construct(private string $subscription_id) {
        
    }

    #[\Override]
    static function fromMessage(array $message): self {
        if (count($message) < 2) {
            throw new \InvalidArgumentException('Missing subscription ID');
        }

        return new self($message[1]);
    }

    #[\Override]
    public function __invoke(Context $context): \Generator {
        ($context->subscriptions)($this->subscription_id);
        yield Factory::closed($this->subscription_id);
    }
}
