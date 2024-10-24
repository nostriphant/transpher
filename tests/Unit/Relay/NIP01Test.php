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

describe('generic (https://nips.nostr.com/1#from-relay-to-client-sending-events-and-notices)', function () {
    it('responds with a NOTICE on null message', function () {
        $context = context();

        Relay::handle('null', $context);

        expect($context->relay)->toHaveReceived([
            ['NOTICE', 'Invalid message']
        ]);
    });

    it('responds with a NOTICE on unsupported message types', function () {
        $context = context();

        Relay::handle('["UNKNOWN"]', $context);

        expect($context->relay)->toHaveReceived([
            ['NOTICE', 'Message type UNKNOWN not supported']
        ]);
    });
});

describe('EVENT', function() {
    it('accepts a kind 1 and answers with OK', function () {
        $context = context();

        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');
        Relay::handle($event, $context);

        expect($context->relay)->toHaveReceived([
            ['OK', $event()[1]['id'], true, '']
        ]);
    });
});

describe('REQ', function () {
    it('replies NOTICE Invalid message on non-existing filters', function () {
        $context = context();

        Relay::handle(json_encode(['REQ']), $context);

        expect($context->relay)->toHaveReceived([
            ['NOTICE', 'Invalid message']
        ]);
    });
    it('replies CLOSED on empty filters', function () {
        $context = context();

        Relay::handle(json_encode(['REQ', $id = uniqid(), []]), $context);

        expect($context->relay)->toHaveReceived([
            ['CLOSED', $id, 'Subscription filters are empty']
        ]);
    });
});

describe('CLOSE', function () {
    it('responds with a NOTICE on missing subscription-id', function () {
        $context = context();

        Relay::handle(json_encode(['CLOSE']), $context);

        expect($context->relay)->toHaveReceived([
            ['NOTICE', 'Missing subscription ID']
        ]);
    });
});


describe('Kinds (https://nips.nostr.com/1#kinds)', function () {


    it('sends a notice for undefined event kinds', function () {
        $context = context();

        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, -1, 'Hello World');
        Relay::handle($event, $context);

        expect($context->relay)->toHaveReceived([
            ['NOTICE', 'Undefined event kind -1']
        ]);
    });

    it('stores regular (1000 <= n < 10000) events', function () {
        $context = context();
        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        for ($kind = 1000; $kind < 10000; $kind += 5000) {
            $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, $kind, 'Hello World');
            Relay::handle($event, $context);

            expect(isset($context->events[$event()[1]['id']]))->toBeTrue();
        }
    });

    it('stores regular (4 <= n < 45) events', function () {
        $context = context();

        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        for ($kind = 4; $kind < 45; $kind++) {
            $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, $kind, 'Hello World');
            Relay::handle($event, $context);

            expect(isset($context->events[$event()[1]['id']]))->toBeTrue();
        }
    });

    it('stores regular (n == 1 || n == 2) events', function () {
        $context = context();
        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        for ($kind = 1; $kind < 3; $kind++) {
            $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, $kind, 'Hello World');
            Relay::handle($event, $context);

            expect(isset($context->events[$event()[1]['id']]))->toBeTrue();
        }
    });

    it('replaces replaceable (10000 <= n < 20000) events, keeping only the last one (based on pubkey & kind)', function () {
        $context = context();

        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        for ($kind = 10000; $kind < 20000; $kind += 5000) {
            $original_event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, $kind, 'Hello World');
            Relay::handle($original_event, $context);

            expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

            $updated_event = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Updated: hello World', time() + 100);
            Relay::handle($updated_event, $context);

            expect(isset($context->events[$original_event()[1]['id']]))->ToBeFalse();
            expect(isset($context->events[$updated_event()[1]['id']]))->toBeTrue();
        }
    });

    it('keeps replaceable (10000 <= n < 20000) events, when same created_at with lowest id (based on pubkey & kind)', function () {
        $context = context();

        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        for ($kind = 10000; $kind < 20000; $kind += 5000) {
            $time = time();
            $event1 = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Hello World', $time);
            $event2 = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Updated: hello World', $time);
            if ($event1()[1]['id'] < $event2()[1]['id']) {
                $original_event = $event1;
                $updated_event = $event2;
            } else {
                $original_event = $event2;
                $updated_event = $event1;
            }

            Relay::handle($original_event, $context);

            expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

            Relay::handle($updated_event, $context);

            expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();
            expect(isset($context->events[$updated_event()[1]['id']]))->toBeFalse();
        }
    });

    it('replaces replaceable (n == 0) events, keeping only the last one (based on pubkey & kind)', function () {
        $context = context();
        $kind = 0;
        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        $original_event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, $kind, 'Hello World');
        Relay::handle($original_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

        $updated_event = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Updated: hello World', time() + 100);
        Relay::handle($updated_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->ToBeFalse();
        expect(isset($context->events[$updated_event()[1]['id']]))->toBeTrue();
    });
    it('keeps replaceable (n == 0) events, when same created_at with lowest id (based on pubkey & kind)', function () {
        $context = context();
        $kind = 0;
        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        $time = time();
        $event1 = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Hello World', $time);
        $event2 = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Updated: hello World', $time);
        if ($event1()[1]['id'] < $event2()[1]['id']) {
            $original_event = $event1;
            $updated_event = $event2;
        } else {
            $original_event = $event2;
            $updated_event = $event1;
        }

        Relay::handle($original_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

        Relay::handle($updated_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();
        expect(isset($context->events[$updated_event()[1]['id']]))->toBeFalse();
    });

    it('replaces replaceable (n == 3) events, keeping only the last one (based on pubkey & kind)', function () {
        $context = context();
        $kind = 3;
        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        $original_event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, $kind, 'Hello World');
        Relay::handle($original_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

        $updated_event = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Updated: hello World', time() + 100);
        Relay::handle($updated_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->ToBeFalse();
        expect(isset($context->events[$updated_event()[1]['id']]))->toBeTrue();
    });

    it('keeps replaceable (n == 3) events, when same created_at with lowest id (based on pubkey & kind)', function () {
        $context = context();
        $kind = 3;
        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        $time = time();
        $event1 = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Hello World', $time);
        $event2 = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Updated: hello World', $time);
        if ($event1()[1]['id'] < $event2()[1]['id']) {
            $original_event = $event1;
            $updated_event = $event2;
        } else {
            $original_event = $event2;
            $updated_event = $event1;
        }

        Relay::handle($original_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

        Relay::handle($updated_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();
        expect(isset($context->events[$updated_event()[1]['id']]))->toBeFalse();
    });

    it('does not store ephemeral (20000 <= kind < 30000) events', function () {
        $context = context();

        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        for ($kind = 20000; $kind < 30000; $kind += 5000) {
            $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, $kind, 'Hello World');
            Relay::handle($event, $context);

            expect(isset($context->events[$event()[1]['id']]))->toBeFalse();
        }
    });

    it('replaces addressable (30000 <= n < 40000) events, keeping only the last one (based on pubkey, kind and d)', function () {
        $context = context();

        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        for ($kind = 30000; $kind < 40000; $kind += rand(100, 5000)) {
            $original_event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, $kind, 'Hello World', ['d', 'my-d-tag-value']);
            Relay::handle($original_event, $context);

            expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

            $updated_event = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Updated: hello World', time() + 100, ['d', 'my-d-tag-value']);
            Relay::handle($updated_event, $context);

            expect(isset($context->events[$original_event()[1]['id']]))->ToBeFalse();
            expect(isset($context->events[$updated_event()[1]['id']]))->toBeTrue();
        }
    });
    it('keeps addressable (30000 <= n < 40000) events, when same created_at with lowest id (based on pubkey, kind and d)', function () {
        $context = context();

        $sender_key = \rikmeijer\Transpher\Nostr\Key::generate();
        for ($kind = 30000; $kind < 40000; $kind += 5000) {
            $time = time();
            $event1 = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Hello World', $time, ['d', 'my-d-tag-value']);
            $event2 = \rikmeijer\Transpher\Nostr\Message\Factory::eventAt($sender_key, $kind, 'Updated: hello World', $time, ['d', 'my-d-tag-value']);
            if ($event1()[1]['id'] < $event2()[1]['id']) {
                $original_event = $event1;
                $updated_event = $event2;
            } else {
                $original_event = $event2;
                $updated_event = $event1;
            }

            Relay::handle($original_event, $context);

            expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

            Relay::handle($updated_event, $context);

            expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();
            expect(isset($context->events[$updated_event()[1]['id']]))->toBeFalse();
        }
    });
});