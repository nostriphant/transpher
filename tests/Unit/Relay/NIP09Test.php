<?php

use nostriphant\NIP01Tests\Functions as NIP01TestFunctions;
use nostriphant\TranspherTests\Factory;
use nostriphant\NIP01\Key;
use nostriphant\NIP01\Message;
use function Pest\store,
             Pest\incoming;

$references = [
    ['e', fn(Message $message) => $message()[1]['id']],
    ['k', fn(Message $message) => $message()[1]['kind']],
    ['a', fn(Message $message) => $message()[1]['kind'] . ':' . $message()[1]['pubkey'] . ':a-random-d-tag']
];

it('SHOULD delete or stop publishing any referenced events that have an identical pubkey as the deletion request.', function ($tag, $value_callback) {
    $store = store();

    $sender_key = NIP01TestFunctions::key_sender();
    $message = Factory::event($sender_key, 1, 'Hello World', ['d', 'a-random-d-tag']);
    $referenced_value = $value_callback($message);

    expect(\Pest\handle($message, incoming(store: $store)))->toHaveReceived(
            ['OK']
    );

    $recipient = \Pest\handle(Message::req($subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]), incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );

    $delete_event = Factory::event($sender_key, 5, 'sent by accident', [$tag, $referenced_value]);
    expect(\Pest\handle($delete_event, incoming(store: $store)))->toHaveReceived(
            ['OK', $delete_event()[1]['id'], true]
    );

    expect(isset($store[$delete_event()[1]['id']]))->toBeTrue();
    expect(isset($store[$message()[1]['id']]))->toBeFalse();

    $recipient = \Pest\handle(Message::req($subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]), incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('sent by accident');
                    expect($event['kind'])->toBe(5);
                }],
            ['EOSE', $subscription_id]
    );
})->with($references);

it('SHOULD NOT delete or stop publishing any referenced events that have an different pubkey as the deletion request.', function ($tag, $value_callback) {
    $store = store();

    $sender_key = NIP01TestFunctions::key_sender();
    $message = Factory::event($sender_key, 1, 'Hello World', ['d', 'a-random-d-tag']);
    $referenced_value = $value_callback($message);

    $recipient = \Pest\handle($message, incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['OK']
    );

    $recipient = \Pest\handle(Message::req($subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]), incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );

    $delete_event = Factory::event(Key::generate(), 5, 'sent by accident', [$tag, $referenced_value]);
    $recipient = \Pest\handle($delete_event, incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['OK', $delete_event()[1]['id'], true]
    );

    expect(isset($store[$delete_event()[1]['id']]))->toBeTrue();
    expect(isset($store[$message()[1]['id']]))->toBeTrue();

    $recipient = \Pest\handle(Message::req($subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]), incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );
})->with($references);

it('When an a tag is used, relays SHOULD delete all versions of the replaceable event up to the created_at timestamp of the deletion request event.', function () {
    $store = store();

    $sender_key = NIP01TestFunctions::key_sender();
    $message = Factory::event($sender_key, 1, 'Hello World', ['d', 'a-random-d-tag']);

    $recipient = \Pest\handle($message, incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['OK']
    );

    $recipient = \Pest\handle(Message::req($subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]), incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );

    $delete_event = Factory::eventAt($sender_key, 5, 'sent by accident', time() - 60, ['a', $message()[1]['kind'] . ':' . $message()[1]['pubkey'] . ':a-random-d-tag']);
    $recipient = \Pest\handle($delete_event, incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['OK', $delete_event()[1]['id'], true]
    );

    expect(isset($store[$delete_event()[1]['id']]))->toBeTrue();
    expect(isset($store[$message()[1]['id']]))->toBeTrue();

    $recipient = \Pest\handle(Message::req($subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())], 'kinds' => [1]]), incoming(store: $store));
    expect($recipient)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );
});
