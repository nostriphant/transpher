<?php

namespace Transpher\Nostr\Relay;

use Functional\Functional;
use function \Functional\if_else, \Functional\first, \Functional\not, \Functional\identical, \Functional\partial_left;

/**
 * Description of Subscriptions
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
 class Subscriptions {
    
    private static array $subscriptions = [];

    static function apply(array $event): bool {
        return empty(self::$subscriptions) ? false : null !== first(self::$subscriptions, fn(array $subscription) => $subscription[0]($event));
    }
    static function subscribe(string $subscriptionId, Filter $matcher, callable $success) : void {
        $subscription_relay = if_else($matcher, fn($event) => $success($subscriptionId, $event), Functional::false);
        self::$subscriptions[$subscriptionId] = [$subscription_relay];
    }
    static function unsubscribe(string $subscriptionId) : void {
        unset(self::$subscriptions[$subscriptionId]);
    }
    
}
