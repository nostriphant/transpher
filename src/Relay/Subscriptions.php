<?php

namespace nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Filters;
use function \Functional\if_else;
use \nostriphant\Transpher\Relay\Sender;

class Subscriptions {

    private array $subscriptions = [];

    public function __construct(private Sender $relay) {
        
    }

    public function __invoke(mixed ...$args): mixed {
        return match (true) {
            func_num_args() === 0 => count($this->subscriptions),
            $args[0] instanceof \Closure => self::apply($this->subscriptions, $args[0]),
            is_string($args[0]) && count($args) === 1 => self::unsubscribe($this->subscriptions, $args[0]),
            is_string($args[0]) => self::subscribe($this->subscriptions, $this->relay, $args[0], $args[1]),
        };
    }

    static function apply(array &$subscriptions, callable $applier): mixed {
        return array_find($subscriptions, $applier);
    }

    static function subscribe(array &$subscriptions, Sender $relay, string $subscription_id, Filters $filters): void {
        $subscriptions[$subscription_id] = if_else($filters, fn() => $relay, fn() => false);
    }

    static function unsubscribe(array &$subscriptions, string $subscription_id): void {
        unset($subscriptions[$subscription_id]);
    }
}
