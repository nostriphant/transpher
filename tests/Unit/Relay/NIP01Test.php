<?php

use rikmeijer\Transpher\Relay\Incoming\Context;
use rikmeijer\Transpher\Relay;

function context(): Context {
    return new Context(
            events: new class([]) implements rikmeijer\Transpher\Relay\Store {

                use \rikmeijer\Transpher\Nostr\Store;
            },
            relay: new class implements rikmeijer\Transpher\Relay\Sender {

                public array $messages = [];

                #[\Override]
                public function __invoke(mixed $json): bool {
                    $this->messages[] = $json;
                    return true;
                }
            }
    );
}

expect()->extend('toHaveReceived', function (array $expected_messages) {
    expect($this->value->messages)->toHaveCount(count($expected_messages));
    foreach ($this->value->messages as $message) {
        expect($message())->toBe(array_shift($expected_messages));
    }
});

it('accepts a kind 1 event and answers with OK', function () {
    $context = context();

    $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
    $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');

    Relay::handle($event, $context);


    expect($context->relay)->toHaveReceived([
        ['OK', $event()[1]['id'], true, '']
    ]);
});

it('responds with a NOTICE on null message', function () {
    $context = context();

    Relay::handle('null', $context);

    expect($context->relay)->toHaveReceived([
        ['NOTICE', 'Invalid message']
    ]);
});
