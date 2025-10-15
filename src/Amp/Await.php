<?php

namespace nostriphant\Transpher\Amp;

readonly class Await {

    public function __construct(private \Closure $what, private \Closure $then) {
        
    }

    public function __invoke(callable $callback): void {
        $callback(call_user_func($this->what));
        call_user_func($this->then);
    }
}
