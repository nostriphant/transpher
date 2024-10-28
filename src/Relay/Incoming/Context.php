<?php

namespace rikmeijer\Transpher\Relay\Incoming;

use function \Functional\map;

class Context {

    public function __construct(
            readonly public ?\rikmeijer\Transpher\Relay\Subscriptions $subscriptions = null,
            readonly public ?\rikmeijer\Transpher\Relay\Store $events = null,
            readonly public ?\rikmeijer\Transpher\Relay\Sender $relay = null
    ) {
        
    }

    static function merge(Context $context, Context ...$contexts) {
        return new self(...array_merge(self::filterProperties($context), ...map($contexts, [__CLASS__, 'filterProperties'])));
    }

    static function filterProperties(Context $context) {
        return array_filter(get_object_vars($context));
    }
}
