<?php

namespace nostriphant\Transpher\Relay\Incoming\Auth;

readonly class Limits {

    static function construct(
    ): \nostriphant\Transpher\Relay\Limits {
        return \nostriphant\Transpher\Relay\Incoming\Event\Limits::fromEnv();
    }

    static function fromEnv(): \nostriphant\Transpher\Relay\Limits {
        return \nostriphant\Transpher\Relay\Limits::fromEnv('AUTH', __CLASS__);
    }
}
