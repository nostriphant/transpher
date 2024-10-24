<?php

use rikmeijer\Transpher\Relay\Incoming\Context;
use rikmeijer\Transpher\Relay\Incoming\Factory;

it('accepts a kind 1 event and answers with OK', function () {
    $context = new Context(
            events: new class([]) implements rikmeijer\Transpher\Relay\Store {

                use \rikmeijer\Transpher\Nostr\Store;
            },
            relay: new class implements rikmeijer\Transpher\Relay\Sender {

                #[\Override]
                public function __invoke(mixed $json): bool {
                    return true;
                }
            }
    );

    $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
    $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World')();
    $incoming = Factory::make($event);

    $expected = [
        ['OK', $event[1]['id'], true, '']
    ];

    foreach ($incoming($context) as $message) {
        expect($message())->toBe(array_shift($expected));
    }
});
