<?php

namespace nostriphant\Transpher\Relay\Incoming;

use function \Functional\map;

class Context {

    public function __construct(
            readonly public ?\nostriphant\Transpher\Relay\Subscriptions $subscriptions = null,
            readonly public ?\nostriphant\Transpher\Relay\Store $events = null,
            readonly public ?\nostriphant\Transpher\Relay\Sender $relay = null
    ) {
        
    }

    static function merge(Context $context, Context ...$contexts) {
        return new self(...array_merge(self::filterProperties($context), ...map($contexts, [__CLASS__, 'filterProperties'])));
    }

    static function filterProperties(Context $context) {
        return array_filter(get_object_vars($context));
    }
}
