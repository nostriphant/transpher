<?php

namespace rikmeijer\Transpher\Nostr\Relay;

use Functional\Functional;
use function \Functional\if_else, \Functional\first;
use rikmeijer\Transpher\Nostr\Message;
use rikmeijer\Transpher\Nostr\Event;
use rikmeijer\Transpher\Filters;

/**
 * Description of Subscriptions
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
 class Subscriptions {
    
    private static array $subscriptions = [];

    static function apply(Event $event): bool {
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
    static function subscribe(string $subscriptionId, array $prototype, callable $relay) : Filter {
        $matcher = new Filters($prototype);
        self::$subscriptions[$subscriptionId] = if_else($matcher, fn() => $relay, fn() => false);
        return $matcher;
    }
    static function unsubscribe(string $subscriptionId) : void {
        unset(self::$subscriptions[$subscriptionId]);
    }
    
}
