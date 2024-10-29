<?php

use nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Message\Factory;
use function Pest\context;

it('replaces replaceable (n == 3; follow list) events, keeping only the last one (based on pubkey & kind)', function () {
    $context = context();
    $kind = 3;
    $sender_key = \Pest\key_sender();;
    $original_event = Factory::event($sender_key, $kind, 'Hello World');
    Relay::handle($original_event, $context);

    expect(isset($context->events[$original_event()[1]['id']]))->toBeTrue();

    $updated_event = Factory::eventAt($sender_key, $kind, 'Updated: hello World', time() + 100);
    Relay::handle($updated_event, $context);

    expect(isset($context->events[$original_event()[1]['id']]))->ToBeFalse();
    expect(isset($context->events[$updated_event()[1]['id']]))->toBeTrue();
});

it('keeps replaceable (n == 3; follow list) events, when same created_at with lowest id (based on pubkey & kind)', function () {
    $context = context();
    $kind = 3;
    $sender_key = \Pest\key_sender();;
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
