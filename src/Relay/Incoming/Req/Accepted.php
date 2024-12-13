<?php

namespace nostriphant\Transpher\Relay\Incoming\Req;

use function \Functional\partial_left;
use nostriphant\Transpher\Nostr\Message\Factory;

class Accepted {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions,
            private \nostriphant\Transpher\Relay\Limits $limits,
    ) {

    }

    public function __invoke(string $subscription_id, array $filter_prototypes): mixed {
        yield from ($this->limits)($this->subscriptions)(
                        rejected: fn(string $reason) => yield Factory::closed($subscription_id, $reason),
                        accepted: function () use ($subscription_id, $filter_prototypes) {
                            ($this->subscriptions)($subscription_id, $filter_prototypes);
                            ($this->events)(...$filter_prototypes)(\nostriphant\Transpher\Stores\Results::copyTo($events));
                            yield from array_map(partial_left([Factory::class, 'requestedEvent'], $subscription_id), $events);
                            yield Factory::eose($subscription_id);
        });
    }
}
