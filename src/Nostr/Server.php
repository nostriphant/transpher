<?php

namespace Transpher\Nostr;

use \Transpher\Nostr;
use \Transpher\Filters;
use Functional\Functional;
use function \Functional\each, \Functional\partial_left;

/**
 * Description of Server
 *
 * @author Rik Meijer <rmeijer@wemanity.com>
 */
class Server {
    
    static function listen(array $message, callable $subscriptions) {
        $type = array_shift($message);
        if (is_callable([self::class, $type]) === false) {
            yield Nostr::notice('Message type ' . $type . ' not supported');
        } else {
            try {
                yield from call_user_func([self::class, $type], $subscriptions, ...$message);
            } catch (\ArgumentCountError $ex) {
                yield Nostr::notice('Invalid message');
            }
        }
    }
    
    static function event(callable $subscriptions, array $event) : \Generator { 
        yield from $subscriptions($event);
        yield Nostr::accept($event['id']);
    }
    
    static function close(callable $subscriptions, string $subscriptionId) : \Generator {
        yield from $subscriptions($subscriptionId, null);
        yield Nostr::closed($subscriptionId);
    }
    
    static function req(callable $subscriptions, string $subscriptionId, array $subscription) : \Generator {
        if (empty($subscription)) {
            yield Nostr::closed($subscriptionId, 'Subscription filters are empty');
        } else {
            yield from $subscriptions($subscriptionId, Filters::constructSubscription($subscription));
            yield Nostr::eose($subscriptionId);
        }
    }
    
    static function relay(callable $to, string $subscriptionId, array $event) : bool {
        $to(
           Nostr::subscribedEvent($subscriptionId, $event),
           Nostr::eose($subscriptionId)
        );
        return true;
    }
}
