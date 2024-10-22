<?php

namespace rikmeijer\TranspherTests\Unit;

use rikmeijer\Transpher\Nostr\Event;

class Functions {

    static function vectors(string $name): object {
        return json_decode(file_get_contents(__DIR__ . '/vectors/' . $name . '.json'), false);
    }

    static function event(array $event): Event {
        return new Event(...array_merge([
                    'id' => '',
                    'pubkey' => '',
                    'created_at' => time(),
                    'kind' => 1,
                    'content' => 'Hello World',
                    'sig' => '',
                    'tags' => []
                        ], $event));
    }

    static function context(array $context) {
        return new \rikmeijer\Transpher\Relay\Incoming\Context(...array_merge([
                    'events' => \Mockery::mock(\rikmeijer\Transpher\Relay\Store::class),
            'relay' => \Mockery::mock(\rikmeijer\Transpher\Relay\Sender::class)
        ], $context));
    }
}
