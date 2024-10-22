<?php

namespace rikmeijer\Transpher\Relay\Incoming;

use function \Functional\map;

readonly class Context {

    public function __construct(
            public ?\rikmeijer\Transpher\Relay\Store $events = null,
            public ?\rikmeijer\Transpher\Relay\Sender $relay = null
    ) {
        
    }

    static function merge(Context $context, Context ...$contexts) {
        return new self(...array_merge(array_filter(get_object_vars($context)), ...map($contexts, fn(Context $context) => array_filter(get_object_vars($context)))));
    }
}
