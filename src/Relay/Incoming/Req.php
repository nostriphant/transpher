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
readonly class Req {

    private string $subscription_id;
    private array $filters;

    public function __construct(array $message) {
        if (count($message) < 3) {
            throw new \InvalidArgumentException('Invalid message');
        }

        $this->subscription_id = $message[1];
        $this->filters = array_filter(array_slice($message, 2));
    }

    public function __invoke(Context $context): \Generator {
        if (count($this->filters) === 0) {
            yield Factory::closed($this->subscription_id, 'Subscription filters are empty');
        } else {
            $filters = Condition::makeFiltersFromPrototypes(...$this->filters);
            ($context->subscriptions)($this->subscription_id, if_else($filters, fn() => $context->relay, fn() => false));
            $subscribed_events = fn(string $subscriptionId) => map(($context->events)($filters), partial_left([Factory::class, 'requestedEvent'], $subscriptionId));
            yield from $subscribed_events($this->subscription_id);
            yield Factory::eose($this->subscription_id);
        }
    }
}
