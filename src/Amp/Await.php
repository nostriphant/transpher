<?php

namespace nostriphant\Transpher\Amp;

readonly class Await {

    public function __construct(private \Closure $what) {
        
    }

    public function __invoke(callable $callback): void {
        $callback(call_user_func($this->what));
    }
}
