<?php

namespace nostriphant\Transpher\Relay;

use \nostriphant\NIP01\Transmission;
use nostriphant\NIP01\Message;
use nostriphant\NIP01\Event;
use nostriphant\Stores\Engine\Memory\Condition;
use nostriphant\Stores\Conditions;

class Subscriptions {

    private array $subscriptions = [];

    public function __construct(private Transmission $relay) {
        
    }

    public function __invoke(mixed ...$args): mixed {
        return match (true) {
            func_num_args() === 0 => count($this->subscriptions),
            $args[0] instanceof Event => self::apply($this->subscriptions, $args[0]),
            is_string($args[0]) && count($args) === 1 => self::unsubscribe($this->subscriptions, $args[0]),
            is_string($args[0]) => self::subscribe($this->subscriptions, $this->relay, $args[0], $args[1]),
        };
    }

    static function apply(array &$subscriptions, Event $event): mixed {
        array_find($subscriptions, function (callable $subscription, string $subscriptionId) use ($event) {
            $to = $subscription($event);
            if ($to === false) {
                return false;
            }
            $to(Message::event($subscriptionId, $event));
            $to(Message::eose($subscriptionId));
            return true;
        });
        yield Message::ok($event->id, true, '');
    }

    static function subscribe(array &$subscriptions, Transmission $relay, string $subscription_id, array $filter_prototypes): void {
        $test = Condition::makeConditions(new Conditions($filter_prototypes));
        $subscriptions[$subscription_id] = fn(Event $event) => $test($event) ? $relay : fn() => false;
    }

    static function unsubscribe(array &$subscriptions, string $subscription_id): void {
        unset($subscriptions[$subscription_id]);
    }
}
