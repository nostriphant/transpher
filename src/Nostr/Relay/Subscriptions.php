<?php

namespace Transpher\Nostr\Relay;

use Functional\Functional;
use function \Functional\if_else, \Functional\first, \Functional\not, \Functional\identical, \Functional\partial_left;

/**
 * Description of Subscriptions
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
 readonly class Subscriptions {
    
    public function __construct(private array $subscriptions = []) {
    }
    public function __invoke(array $event): bool {
        return empty($this->subscriptions) ? false : null !== first($this->subscriptions, fn(array $subscription) => $subscription[1]($event));
    }
    
    static function makeStore(?Subscriptions $subscriptions = null) : self {
        static $_subscriptions = new self();
        if (isset($subscriptions)) {
            $_subscriptions = $subscriptions;
        }
        return $_subscriptions;
    }
    
    static function subscribe(string $subscriptionId, callable $matcher, callable $success) : void {
        $subscription_relay = if_else($matcher, fn($event) => $success($subscriptionId, $event), Functional::false);
        self::makeStore(new self(array_merge(Subscriptions::makeStore()->subscriptions, [[$subscriptionId, $subscription_relay]])));
    }
    static function unsubscribe(string $subscriptionId) : void {
        self::makeStore(new self(array_filter(Subscriptions::makeStore()->subscriptions, fn(array $item) => $item[0] !== $subscriptionId)));
    }
    
}
