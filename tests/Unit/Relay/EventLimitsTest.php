<?php

use nostriphant\Transpher\Relay\Incoming\Event\Limits;
use nostriphant\Transpher\Relay\Incoming\Constraint\Result;

it('SHOULD ignore created_at limits for regular events', function () {
    $limits = Limits::construct();

    $limit = $limits(\Pest\rumor(kind: 1, pubkey: \Pest\pubkey_sender(), created_at: time() - (60 * 60 * 24) - 5)(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);

    $limit = $limits(\Pest\rumor(kind: 1, pubkey: \Pest\pubkey_sender(), created_at: time() + (60 * 15) + 5)(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);
});

it('SHOULD send the client an OK result saying the event was not stored for the created_at timestamp not being within the permitted limits.', function (int $kind) {
    $limits = Limits::construct();

    $limit = $limits(\Pest\rumor(kind: $kind, pubkey: \Pest\pubkey_sender(), created_at: time() - (60 * 60 * 24) - 5)(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('the event created_at field is out of the acceptable range (-24h) for this relay'));

    $limit = $limits(\Pest\rumor(kind: $kind, pubkey: \Pest\pubkey_sender(), created_at: time() + (60 * 15) + 5)(\Pest\key_sender()));
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('the event created_at field is out of the acceptable range (+15min) for this relay'));
    
})->with([
    'replaceable' => 0,
    'ephemeral' => 20000,
    'addressable' => 30000
]);

it('can be configured for event kinds to always allow. Leave empty to allow any.', function () {
    $limits = Limits::construct(
            kind_whitelist: [1]
    );

    $limit = $limits(\Pest\rumor(kind: 1, pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);

    $limit = $limits(\Pest\rumor(kind: 2, pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('event kind is not whitelisted'));
});

it('can be configured for event kinds to always deny. Leave empty to allow any.', function () {
    $limits = Limits::construct(
            kind_blacklist: [1]
    );

    $limit = $limits(\Pest\rumor(kind: 1, pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('event kind is blacklisted'));

    $limit = $limits(\Pest\rumor(kind: 2, pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);
});


it('can be configured for event content max limit.', function () {
    $limits = Limits::construct(
            content_maxlength: 10
    );

    $limit = $limits(\Pest\rumor(kind: 1, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 1, pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);
});


it('can be configured for event content max limit, only for a certain event kind.', function () {
    $limits = Limits::construct(
            content_maxlength: [10, 1]
    );

    $limit = $limits(\Pest\rumor(kind: 1, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 0, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);
});


it('can be configured for event content max limit, only for certain event kinds.', function () {
    $limits = Limits::construct(
            content_maxlength: [10, 1, 5]
    );

    $limit = $limits(\Pest\rumor(kind: 1, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 2, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 3, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 4, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 5, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 0, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);
});

it('SHOULD ignore created_at limits through env-vars for regular events', function () {
    putenv('LIMIT_EVENT_CREATED_AT_LOWER_DELTA=60');
    putenv('LIMIT_EVENT_CREATED_AT_UPPER_DELTA=15');
    $limits = Limits::fromEnv();

    $limit = $limits(\Pest\rumor(kind: 1, pubkey: \Pest\pubkey_sender(), created_at: time() - 61)(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);

    $limit = $limits(\Pest\rumor(kind: 1, pubkey: \Pest\pubkey_sender(), created_at: time() + 16)(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);
});

it('SHOULD send the client an OK result saying the event was not stored for the created_at timestamp not being within the permitted limits through env-vars.', function (int $kind) {
    putenv('LIMIT_EVENT_CREATED_AT_LOWER_DELTA=60');
    putenv('LIMIT_EVENT_CREATED_AT_UPPER_DELTA=15');
    $limits = Limits::fromEnv();

    $limit = $limits(\Pest\rumor(kind: $kind, pubkey: \Pest\pubkey_sender(), created_at: time() - 62)(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('the event created_at field is out of the acceptable range (-60sec) for this relay'));

    $limit = $limits(\Pest\rumor(kind: $kind, pubkey: \Pest\pubkey_sender(), created_at: time() + 17)(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('the event created_at field is out of the acceptable range (+15sec) for this relay'));
})->with([
    'replaceable' => 0,
    'ephemeral' => 20000,
    'addressable' => 30000
]);

it('can be configured through env-vars for event kinds to always allow. Leave empty to allow any.', function () {
    putenv('LIMIT_EVENT_KIND_WHITELIST=1');
    $limits = Limits::fromEnv();

    $limit = $limits(\Pest\rumor(kind: 1, pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);

    $limit = $limits(\Pest\rumor(kind: 2, pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('event kind is not whitelisted'));
    putenv('LIMIT_EVENT_KIND_WHITELIST');
});

it('can be configured through env-vars for event kinds to always deny. Leave empty to allow any.', function () {
    putenv('LIMIT_EVENT_KIND_BLACKLIST=1');
    $limits = Limits::fromEnv();

    $limit = $limits(\Pest\rumor(kind: 1, pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('event kind is blacklisted'));

    $limit = $limits(\Pest\rumor(kind: 2, pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);
    putenv('LIMIT_EVENT_KIND_BLACKLIST');
});

it('can be configured through env-vars for event content max limit.', function () {
    putenv('LIMIT_EVENT_CONTENT_MAXLENGTH=10');
    $limits = Limits::fromEnv();

    $limit = $limits(\Pest\rumor(kind: 1, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 1, pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);
    putenv('LIMIT_EVENT_CONTENT_MAXLENGTH');
});

it('can be configured through env-vars for event content max limit, only for a certain event kind.', function () {
    putenv('LIMIT_EVENT_CONTENT_MAXLENGTH=10,1');
    $limits = Limits::fromEnv();

    $limit = $limits(\Pest\rumor(kind: 1, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 0, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);
    putenv('LIMIT_EVENT_CONTENT_MAXLENGTH');
});

it('can be configured through env-vars for event content max limit, only for certain event kinds.', function () {
    putenv('LIMIT_EVENT_CONTENT_MAXLENGTH=10,1,5');
    $limits = Limits::fromEnv();

    $limit = $limits(\Pest\rumor(kind: 1, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 2, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 3, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 4, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 5, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(rejected: fn(string $reason) => expect($reason)->toBe('content is longer than 10 bytes'));

    $limit = $limits(\Pest\rumor(kind: 0, content: str_repeat('a', 11), pubkey: \Pest\pubkey_sender())(\Pest\key_sender()));
    expect($limit)->toHaveState(Result::ACCEPTED);
    putenv('LIMIT_EVENT_CONTENT_MAXLENGTH');
});
