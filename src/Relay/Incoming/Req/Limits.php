<?php

namespace nostriphant\Transpher\Relay\Incoming\Req;

use nostriphant\Transpher\Relay\Incoming\Alternate;
use nostriphant\Transpher\Relay\Subscriptions;

readonly class Limits {

    static function construct(
            int $max_filters_per_subscription = 10
    ): \nostriphant\Transpher\Relay\Limits {
        $checks = [];

        if ($max_filters_per_subscription > 0) {
            $checks['subscription filters are empty'] = fn(array $filter_prototypes) => count($filter_prototypes) === 0;
        }
        if ($max_filters_per_subscription > 0) {
            $checks['max number of filters per subscription (' . $max_filters_per_subscription . ') reached'] = fn(array $filter_prototypes) => count($filter_prototypes) > $max_filters_per_subscription;
        }

        return new \nostriphant\Transpher\Relay\Limits($checks);
    }

    static function fromEnv(): \nostriphant\Transpher\Relay\Limits {
        return \nostriphant\Transpher\Relay\Limits::fromEnv('REQ', __CLASS__);
    }
}
