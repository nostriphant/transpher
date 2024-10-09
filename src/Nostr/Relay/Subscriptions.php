<?php

namespace Transpher\Nostr\Relay;

use Functional\Functional;
use function \Functional\if_else, \Functional\first;
use Transpher\Nostr\Message;

/**
 * Description of Subscriptions
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
 class Subscriptions {
    
    private static array $subscriptions = [];

    static function apply(array $event): bool {
        if (empty(self::$subscriptions)) {
            return false;
        }
        return null !== first(self::$subscriptions, function(callable $subscription, string $subscriptionId) use ($event) {
            $to = $subscription($event);
            if ($to === false) {
                return false;
            }
            $to(Message::requestedEvent($subscriptionId, $event));
            $to(Message::eose($subscriptionId));
            return true;
        });
    }
    static function subscribe(string $subscriptionId, Filter $matcher, callable $relay) : void {
        self::$subscriptions[$subscriptionId] = if_else($matcher, fn() => $relay, Functional::false);
    }
    static function unsubscribe(string $subscriptionId) : void {
        unset(self::$subscriptions[$subscriptionId]);
    }
    
}
