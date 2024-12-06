<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\NIP01\Event;

class Results {

    private \Closure $results;

    public function __construct(?\Closure $results = null) {
        $this->results = $results ?? fn() => 0;
    }

    static function copyTo(?array &$results = null): callable {
        $results = $results ?? [];
        return function (Event $event) use (&$results) {
            $results[] = $event;
        };
    }

    public function __invoke(?callable $callback = null): int {
        $callback = $callback ?? fn() => true;
        $count = 0;
        call_user_func($this->results, function (Event $event) use (&$count, $callback) {
            $callback($event);
            $count++;
        });
        return $count;
    }
}
