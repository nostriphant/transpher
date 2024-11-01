<?php

use nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Key;
use function Pest\context;

/**
 * https://github.com/nostr-protocol/nips/commit/72bb8a128b2d7d3c2c654644cd68d0d0fe58a3b1#diff-13387ea5b220b50dc20daca792c07b3b3d2b278dcafc8f134e4369468cab6440
 */
it('replaces addressable (30000 <= n < 40000) events, keeping only the last one (based on pubkey, kind and d)', function () {
    function replaceAddressable(Key $sender_key, int $kind) {
        $context = context();

        $original_event = Factory::event($sender_key, $kind, 'Hello World', ['d', 'my-d-tag-value']);
        $recipient = \Pest\handle($original_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

        $updated_event = Factory::eventAt($sender_key, $kind, 'Updated: hello World', time() + 100, ['d', 'my-d-tag-value']);
        $recipient = \Pest\handle($updated_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->ToBeFalse();
        expect(isset($context->events[$updated_event()[1]['id']]))->toBeTrue();
    }

    $sender_key = \Pest\key_sender();
    for ($kind = 30000; $kind < 40000; $kind += rand(100, 5000)) {
        replaceAddressable($sender_key, $kind);
    }
});
it('keeps addressable (30000 <= n < 40000) events, when same created_at with lowest id (based on pubkey, kind and d)', function () {
    function keepAddressable(Key $sender_key, int $kind) {
        $context = context();

        $time = time();
        $event1 = Factory::eventAt($sender_key, $kind, 'Hello World', $time, ['d', 'my-d-tag-value']);
        $event2 = Factory::eventAt($sender_key, $kind, 'Updated: hello World', $time, ['d', 'my-d-tag-value']);
        if ($event1()[1]['id'] < $event2()[1]['id']) {
            $original_event = $event1;
            $updated_event = $event2;
        } else {
            $original_event = $event2;
            $updated_event = $event1;
        }

        $recipient = \Pest\handle($original_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

        $recipient = \Pest\handle($updated_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();
        expect(isset($context->events[$updated_event()[1]['id']]))->toBeFalse();
    }

    $sender_key = \Pest\key_sender();
    for ($kind = 30000; $kind < 40000; $kind += 5000) {
        keepAddressable($sender_key, $kind);
    }
});
