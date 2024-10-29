<?php

use nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Key;
use nostriphant\Transpher\Nostr\Message;
use function Pest\context;

$references = [
    'e' => fn(Message $message) => $message()[1]['id'],
    'k' => fn(Message $message) => $message()[1]['kind'],
    'a' => fn(Message $message) => $message()[1]['kind'] . ':' . $message()[1]['pubkey'] . ':a-random-d-tag'
];

foreach ($references as $tag => $value_callback) {
    it('SHOULD delete or stop publishing any referenced (' . $tag . ') events that have an identical pubkey as the deletion request.', function () use ($tag, $value_callback) {
        $context = context();

        $sender_key = Key::generate();
        $message = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', ['d', 'a-random-d-tag']);
        $referenced_value = $value_callback($message);

        Relay::handle($message, $context);
        expect($context->reply)->toHaveReceived(
                ['OK']
        );

        Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
        expect($context->reply)->toHaveReceived(
                ['EVENT', $subscription_id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $subscription_id]
        );

        $delete_event = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 5, 'sent by accident', [$tag, $referenced_value]);
        Relay::handle($delete_event, $context);
        expect($context->reply)->toHaveReceived(
                ['OK', $delete_event()[1]['id'], true]
        );

        expect(isset($context->events[$delete_event()[1]['id']]))->toBeTrue();
        expect(isset($context->events[$message()[1]['id']]))->toBeFalse();

        Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
        expect($context->reply)->toHaveReceived(
                ['EVENT', $subscription_id, function (array $event) {
                        expect($event['content'])->toBe('sent by accident');
                        expect($event['kind'])->toBe(5);
                    }],
                ['EOSE', $subscription_id]
        );
    });

    it('SHOULD NOT delete or stop publishing any referenced (' . $tag . ') events that have an different pubkey as the deletion request.', function () use ($tag, $value_callback) {
        $context = context();

        $sender_key = Key::generate();
        $message = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', ['d', 'a-random-d-tag']);
        $referenced_value = $value_callback($message);

        Relay::handle($message, $context);
        expect($context->reply)->toHaveReceived(
                ['OK']
        );

        Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
        expect($context->reply)->toHaveReceived(
                ['EVENT', $subscription_id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $subscription_id]
        );

        $delete_event = \nostriphant\Transpher\Nostr\Message\Factory::event(Key::generate(), 5, 'sent by accident', [$tag, $referenced_value]);
        Relay::handle($delete_event, $context);
        expect($context->reply)->toHaveReceived(
                ['OK', $delete_event()[1]['id'], true]
        );

        expect(isset($context->events[$delete_event()[1]['id']]))->toBeTrue();
        expect(isset($context->events[$message()[1]['id']]))->toBeTrue();

        Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
        expect($context->reply)->toHaveReceived(
                ['EVENT', $subscription_id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $subscription_id]
        );
    });
}

it('When an a tag is used, relays SHOULD delete all versions of the replaceable event up to the created_at timestamp of the deletion request event.', function () {
    $context = context();

    $sender_key = Key::generate();
    $message = \nostriphant\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World', ['d', 'a-random-d-tag']);

    Relay::handle($message, $context);
    expect($context->reply)->toHaveReceived(
            ['OK']
    );

    Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
    expect($context->reply)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );

    $delete_event = \nostriphant\Transpher\Nostr\Message\Factory::eventAt($sender_key, 5, 'sent by accident', time() - 60, ['a', $message()[1]['kind'] . ':' . $message()[1]['pubkey'] . ':a-random-d-tag']);
    Relay::handle($delete_event, $context);
    expect($context->reply)->toHaveReceived(
            ['OK', $delete_event()[1]['id'], true]
    );

    expect(isset($context->events[$delete_event()[1]['id']]))->toBeTrue();
    expect(isset($context->events[$message()[1]['id']]))->toBeTrue();

    Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())], 'kinds' => [1]]]), $context);
    expect($context->reply)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );
});
