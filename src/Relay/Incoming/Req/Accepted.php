<?php

namespace nostriphant\Transpher\Relay\Incoming\Req;

use nostriphant\NIP01\Message;

class Accepted {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions,
            private \nostriphant\Transpher\Relay\Limits $limits,
    ) {

    }

    public function __invoke(string $subscription_id, array $filter_prototypes): mixed {
        yield from ($this->limits)($this->subscriptions)(
                        rejected: fn(string $reason) => yield Message::closed($subscription_id, $reason),
                        accepted: function () use ($subscription_id, $filter_prototypes) {
                            ($this->subscriptions)($subscription_id, $filter_prototypes);
                            yield from iterator_map(($this->events)(...$filter_prototypes), fn(\nostriphant\NIP01\Event $event) => Message::event($subscription_id, get_object_vars($event)));
                            yield Message::eose($subscription_id);
                        });
    }
}
