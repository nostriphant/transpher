<?php

use nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Key;
use nostriphant\Transpher\Nostr\Message;
use function Pest\context;

$references = [
    ['e', fn(Message $message) => $message()[1]['id']],
    ['k', fn(Message $message) => $message()[1]['kind']],
    ['a', fn(Message $message) => $message()[1]['kind'] . ':' . $message()[1]['pubkey'] . ':a-random-d-tag']
];

it('SHOULD delete or stop publishing any referenced events that have an identical pubkey as the deletion request.', function ($tag, $value_callback) {
    $context = context();

    $sender_key = \Pest\key_sender();
    $message = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', ['d', 'a-random-d-tag']);
    $referenced_value = $value_callback($message);

    \Pest\handle($message, $context);
    expect($context->reply)->toHaveReceived(
            ['OK']
    );

    \Pest\handle(new \nostriphant\Transpher\Nostr\Message('REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]), $context);
    expect($context->reply)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );

    $delete_event = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 5, 'sent by accident', [$tag, $referenced_value]);
    \Pest\handle($delete_event, $context);
    expect($context->reply)->toHaveReceived(
            ['OK', $delete_event()[1]['id'], true]
    );

    expect(isset($context->events[$delete_event()[1]['id']]))->toBeTrue();
    expect(isset($context->events[$message()[1]['id']]))->toBeFalse();

    \Pest\handle(new \nostriphant\Transpher\Nostr\Message('REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]), $context);
    expect($context->reply)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('sent by accident');
                    expect($event['kind'])->toBe(5);
                }],
            ['EOSE', $subscription_id]
    );
})->with($references);

it('SHOULD NOT delete or stop publishing any referenced events that have an different pubkey as the deletion request.', function ($tag, $value_callback) {
    $context = context();

    $sender_key = \Pest\key_sender();
    $message = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', ['d', 'a-random-d-tag']);
    $referenced_value = $value_callback($message);

    \Pest\handle($message, $context);
    expect($context->reply)->toHaveReceived(
            ['OK']
    );

    \Pest\handle(new \nostriphant\Transpher\Nostr\Message('REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]), $context);
    expect($context->reply)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );

    $delete_event = \nostriphant\Transpher\Nostr\Message\Factory::event(Key::generate(), 5, 'sent by accident', [$tag, $referenced_value]);
    \Pest\handle($delete_event, $context);
    expect($context->reply)->toHaveReceived(
            ['OK', $delete_event()[1]['id'], true]
    );

    expect(isset($context->events[$delete_event()[1]['id']]))->toBeTrue();
    expect(isset($context->events[$message()[1]['id']]))->toBeTrue();

    \Pest\handle(new \nostriphant\Transpher\Nostr\Message('REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]), $context);
    expect($context->reply)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );
})->with($references);

it('When an a tag is used, relays SHOULD delete all versions of the replaceable event up to the created_at timestamp of the deletion request event.', function () {
    $context = context();

    $sender_key = \Pest\key_sender();
    $message = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', ['d', 'a-random-d-tag']);

    \Pest\handle($message, $context);
    expect($context->reply)->toHaveReceived(
            ['OK']
    );

    \Pest\handle(new \nostriphant\Transpher\Nostr\Message('REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]), $context);
    expect($context->reply)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );

    $delete_event = \nostriphant\Transpher\Nostr\Message\Factory::eventAt($sender_key, 5, 'sent by accident', time() - 60, ['a', $message()[1]['kind'] . ':' . $message()[1]['pubkey'] . ':a-random-d-tag']);
    \Pest\handle($delete_event, $context);
    expect($context->reply)->toHaveReceived(
            ['OK', $delete_event()[1]['id'], true]
    );

    expect(isset($context->events[$delete_event()[1]['id']]))->toBeTrue();
    expect(isset($context->events[$message()[1]['id']]))->toBeTrue();

    \Pest\handle(new \nostriphant\Transpher\Nostr\Message('REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())], 'kinds' => [1]]), $context);
    expect($context->reply)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );
});
