<?php

namespace rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Store;
use rikmeijer\Transpher\Relay\Subscriptions;
use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\Relay\Sender;

/**
 * Description of Req
 *
 * @author rmeijer
 */
readonly class Req implements Incoming {

    private array $filters;

    public function __construct(private string $subscription_id, array ...$filters) {
        $this->filters = array_filter($filters);
    }

    #[\Override]
    static function fromMessage(array $message): self {
        if (count($message) < 3) {
            throw new \InvalidArgumentException('Invalid message');
        }
        return new self(...array_slice($message, 1));
    }

    #[\Override]
    public function __invoke(): callable {
        return function (array|Store $events, Sender $relay): \Generator {
            if (count($this->filters) === 0) {
                yield Factory::closed($this->subscription_id, 'Subscription filters are empty');
            } else {
                $subscription = Subscriptions::subscribe($relay, $this->subscription_id, ...$this->filters);
                $subscribed_events = $events($subscription);
                yield from $subscribed_events($this->subscription_id);
                yield Factory::eose($this->subscription_id);
            }
        };
    }
}
