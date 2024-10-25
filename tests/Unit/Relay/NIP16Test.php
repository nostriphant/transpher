<?php

use rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Key;
use rikmeijer\Transpher\Nostr\Message\Factory;
use function Pest\context;

/**
 * https://github.com/nostr-protocol/nips/commit/72bb8a128b2d7d3c2c654644cd68d0d0fe58a3b1#diff-323123c7f16af7e22b59e4e5649aa3efb339b4c07fb75f91cfe73ceacd276593L12
 */
it('stores regular (1000 <= n < 10000) events', function () {
    $context = context();
    $sender_key = Key::generate();
    for ($kind = 1000; $kind < 10000; $kind += 5000) {
        $event = Factory::event($sender_key, $kind, 'Hello World');
        Relay::handle($event, $context);

        expect(isset($context->events[$event()[1]['id']]))->toBeTrue();
    }
});

it('stores regular (4 <= n < 45) events', function () {
    $context = context();

    $sender_key = Key::generate();
    for ($kind = 4; $kind < 45; $kind++) {
        $event = Factory::event($sender_key, $kind, 'Hello World');
        Relay::handle($event, $context);

        expect(isset($context->events[$event()[1]['id']]))->toBeTrue();
    }
});

it('stores regular (n == 1 || n == 2) events', function () {
    $context = context();
    $sender_key = Key::generate();
    for ($kind = 1; $kind < 3; $kind++) {
        $event = Factory::event($sender_key, $kind, 'Hello World');
        Relay::handle($event, $context);

        expect(isset($context->events[$event()[1]['id']]))->toBeTrue();
    }
});

it('replaces replaceable (10000 <= n < 20000) events, keeping only the last one (based on pubkey & kind)', function () {
    $context = context();

    $sender_key = Key::generate();
    for ($kind = 10000; $kind < 20000; $kind += 5000) {
        $original_event = Factory::event($sender_key, $kind, 'Hello World');
        Relay::handle($original_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

        $updated_event = Factory::eventAt($sender_key, $kind, 'Updated: hello World', time() + 100);
        Relay::handle($updated_event, $context);

        expect(isset($context->events[$original_event()[1]['id']]))->ToBeFalse();
        expect(isset($context->events[$updated_event()[1]['id']]))->toBeTrue();
    }
});

it('keeps replaceable (10000 <= n < 20000) events, when same created_at with lowest id (based on pubkey & kind)', function () {
    $context = context();

    $sender_key = Key::generate();
    for ($kind = 10000; $kind < 20000; $kind += 5000) {
        $time = time();
        $event1 = Factory::eventAt($sender_key, $kind, 'Hello World', $time);
        $event2 = Factory::eventAt($sender_key, $kind, 'Updated: hello World', $time);
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
    $sender_key = Key::generate();
    $original_event = Factory::event($sender_key, $kind, 'Hello World');
    Relay::handle($original_event, $context);

    expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

    $updated_event = Factory::eventAt($sender_key, $kind, 'Updated: hello World', time() + 100);
    Relay::handle($updated_event, $context);

    expect(isset($context->events[$original_event()[1]['id']]))->ToBeFalse();
    expect(isset($context->events[$updated_event()[1]['id']]))->toBeTrue();
});
it('keeps replaceable (n == 0) events, when same created_at with lowest id (based on pubkey & kind)', function () {
    $context = context();
    $kind = 0;
    $sender_key = Key::generate();
    $time = time();
    $event1 = Factory::eventAt($sender_key, $kind, 'Hello World', $time);
    $event2 = Factory::eventAt($sender_key, $kind, 'Updated: hello World', $time);
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
    $sender_key = Key::generate();
    $original_event = Factory::event($sender_key, $kind, 'Hello World');
    Relay::handle($original_event, $context);

    expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

    $updated_event = Factory::eventAt($sender_key, $kind, 'Updated: hello World', time() + 100);
    Relay::handle($updated_event, $context);

    expect(isset($context->events[$original_event()[1]['id']]))->ToBeFalse();
    expect(isset($context->events[$updated_event()[1]['id']]))->toBeTrue();
});

it('keeps replaceable (n == 3) events, when same created_at with lowest id (based on pubkey & kind)', function () {
    $context = context();
    $kind = 3;
    $sender_key = Key::generate();
    $time = time();
    $event1 = Factory::eventAt($sender_key, $kind, 'Hello World', $time);
    $event2 = Factory::eventAt($sender_key, $kind, 'Updated: hello World', $time);
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

    $sender_key = Key::generate();
    for ($kind = 20000; $kind < 30000; $kind += 5000) {
        $event = Factory::event($sender_key, $kind, 'Hello World');
        Relay::handle($event, $context);

        expect(isset($context->events[$event()[1]['id']]))->toBeFalse();
    }
});
