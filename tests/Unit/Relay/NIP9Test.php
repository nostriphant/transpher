<?php

use rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Key;
use function Pest\context;

it('SHOULD NOT delete or stop publishing any referenced events that have an different pubkey as the deletion request.', function () {
    $context = context();

    $sender_key = Key::generate();
    $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');
    $event_id = $event()[1]['id'];
    Relay::handle($event, $context);
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

    $delete_event = \rikmeijer\Transpher\Nostr\Message\Factory::event(Key::generate(), 5, 'sent by accident', ['e', $event_id]);
    Relay::handle($delete_event, $context);
    expect($context->reply)->toHaveReceived(
            ['OK']
    );

    expect(isset($context->events[$event_id]))->toBeTrue();

    Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
    expect($context->reply)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('Hello World');
                }],
            ['EOSE', $subscription_id]
    );
});


it('SHOULD delete or stop publishing any referenced events that have an identical pubkey as the deletion request.', function () {
    $context = context();

    $sender_key = Key::generate();
    $event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');
    $event_id = $event()[1]['id'];
    Relay::handle($event, $context);
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

    $delete_event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 5, 'sent by accident', ['e', $event_id]);
    Relay::handle($delete_event, $context);
    expect($context->reply)->toHaveReceived(
            ['OK']
    );

    expect(isset($context->events[$event_id]))->ToBeFalse();

    Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
    expect($context->reply)->toHaveReceived(
            ['EVENT', $subscription_id, function (array $event) {
                    expect($event['content'])->toBe('sent by accident');
                    expect($event['kind'])->toBe(5);
                }],
            ['EOSE', $subscription_id]
    );
});
