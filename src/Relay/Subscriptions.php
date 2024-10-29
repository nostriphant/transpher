<?php

namespace nostriphant\Transpher\Relay;

class Subscriptions {

    public function __construct(private array &$subscriptions) {
        
    }

    public function __invoke(mixed ...$args): mixed {
        return match (true) {
            $args[0] instanceof \Closure => self::apply($this->subscriptions, $args[0]),
            is_string($args[0]) && count($args) === 1 => self::unsubscribe($this->subscriptions, $args[0]),
            is_string($args[0]) => self::subscribe($this->subscriptions, $args[0], $args[1]),
        };
    }

    static function apply(array &$subscriptions, callable $applier): mixed {
        return array_find($subscriptions, $applier);
    }

    static function subscribe(array &$subscriptions, string $subscription_id, callable $matcher): void {
        $subscriptions[$subscription_id] = $matcher;
    }

    static function unsubscribe(array &$subscriptions, string $subscription_id): void {
        unset($subscriptions[$subscription_id]);
    }
}
