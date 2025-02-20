<?php

use nostriphant\TranspherTests\Factory;

it('replaces replaceable (n == 3; follow list) events, keeping only the last one (based on pubkey & kind)', function () {
    $store = \Pest\store();

    $kind = 3;
    $sender_key = \Pest\key_sender();
    $original_event = Factory::event($sender_key, $kind, 'Hello World');
    $recipient = \Pest\handle($original_event, store: $store);

    expect(isset($store[$original_event()[1]['id']]))->toBeTrue();

    $updated_event = Factory::eventAt($sender_key, $kind, 'Updated: hello World', time() + 10);
    $recipient = \Pest\handle($updated_event, store: $store);

    expect(isset($store[$original_event()[1]['id']]))->ToBeFalse();
    expect(isset($store[$updated_event()[1]['id']]))->toBeTrue();
});

it('keeps replaceable (n == 3; follow list) events, when same created_at with lowest id (based on pubkey & kind)', function () {
    $store = \Pest\store();

    $kind = 3;
    $sender_key = \Pest\key_sender();
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

    $recipient = \Pest\handle($original_event, store: $store);

    expect(isset($store[$original_event()[1]['id']]))->toBeTrue();

    $recipient = \Pest\handle($updated_event, store: $store);

    expect(isset($store[$original_event()[1]['id']]))->toBeTrue();
    expect(isset($store[$updated_event()[1]['id']]))->toBeFalse();
});
