<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Relay\Condition;
use function \Functional\map,
 \Functional\partial_left,
             \Functional\if_else;

/**
 * Description of Req
 *
 * @author rmeijer
 */
readonly class Req implements Type {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions,
            private \nostriphant\Transpher\Relay\Sender $relay,
            
    ) {
        
    }

    #[\Override]
    public function __invoke(array $message): \Generator {
        if (count($message) < 3) {
            throw new \InvalidArgumentException('Invalid message');
        }

        $subscription_id = $message[1];
        $filter_prototypes = array_filter(array_slice($message, 2));

        if (count($filter_prototypes) === 0) {
            yield Factory::closed($subscription_id, 'Subscription filters are empty');
        } else {
            $filters = Condition::makeFiltersFromPrototypes(...$filter_prototypes);
            ($this->subscriptions)($subscription_id, if_else($filters, fn() => $this->relay, fn() => false));
            $subscribed_events = fn(string $subscriptionId) => map(($this->events)($filters), partial_left([Factory::class, 'requestedEvent'], $subscriptionId));
            yield from $subscribed_events($subscription_id);
            yield Factory::eose($subscription_id);
        }
    }
}
