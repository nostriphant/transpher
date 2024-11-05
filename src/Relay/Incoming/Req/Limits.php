<?php

namespace nostriphant\Transpher\Relay\Incoming\Req;

use nostriphant\Transpher\Relay\Incoming\Constraint;
use nostriphant\Transpher\Relay\Subscriptions;

readonly class Limits {

    static function construct(
            ?int $max_per_client = 10
    ): \nostriphant\Transpher\Relay\Limits {
        $checks = [];

        if (isset($max_per_client)) {
            $checks['max number of subscriptions per client (' . $max_per_client . ') reached'] = fn(Subscriptions $subscriptions) => $subscriptions() >= $max_per_client;
        }

        return new \nostriphant\Transpher\Relay\Limits($checks);
    }

    static function fromEnv(): \nostriphant\Transpher\Relay\Limits {
        return \nostriphant\Transpher\Relay\Limits::fromEnv(__CLASS__);
    }
}
