<?php

namespace nostriphant\Transpher;

readonly class AwaitSignal {

    public function __construct(private \Closure $stop) {
        
    }

    public function __invoke(callable $callback): void {
        $signal = \Amp\trapSignal([SIGINT, SIGTERM]);
        $callback($signal);
        call_user_func($this->stop);
    }
}
