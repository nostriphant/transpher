<?php

namespace nostriphant\Transpher\Relay\Incoming\Req;

use nostriphant\Transpher\Relay\Incoming\Constraint;
use nostriphant\Transpher\Relay\Subscriptions;

readonly class Limits {

    static function construct(
            int $max_per_client = 10,
            int $max_filters_per_subscription = 10
    ): \nostriphant\Transpher\Relay\Limits {
        $checks = [];

        if ($max_per_client > 0) {
            $checks['max number of subscriptions per client (' . $max_per_client . ') reached'] = fn(Subscriptions $subscriptions) => $subscriptions() >= $max_per_client;
        }
        if ($max_filters_per_subscription > 0) {
            $checks['max number of filters per subscription (' . $max_filters_per_subscription . ') reached'] = fn(Subscriptions $subscriptions, array $filter_prototypes) => count($filter_prototypes) > $max_filters_per_subscription;
        }

        return new \nostriphant\Transpher\Relay\Limits($checks);
    }

    static function fromEnv(): \nostriphant\Transpher\Relay\Limits {
        return \nostriphant\Transpher\Relay\Limits::fromEnv(__CLASS__);
    }
}
