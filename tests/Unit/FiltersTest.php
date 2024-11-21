<?php

use nostriphant\Transpher\Relay\Condition;
use nostriphant\Transpher\Nostr\Subscription;
use function \Pest\event;

it('filters p-tags', function() {
    $filters = Subscription::make(Condition::map(), ['#p' => ['RandomPTag']]);

    expect($filters(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
});


it('filters e-tags', function() {
    $filters = Subscription::make(Condition::map(), ['#e' => ['RandomEventId']]);

    expect($filters(event(['tags' => [['e', 'RandomEventId']]])))->toBeTrue();
});

it('filters created since', function() {
    $time = time();
    $filters = Subscription::make(Condition::map(), ['since' => $time - 100]);

    expect($filters(event(['created_at' => $time])))->toBeTrue();
});

it('filters created until', function() {
    $time = time();
    $filters = Subscription::make(Condition::map(), ['until' => $time + 100]);

    expect($filters(event(['created_at' => $time])))->toBeTrue();
});


it('filters maximum number of items', function() {
    $time = time();
    $filters = Subscription::make(Condition::map(), ['until' => $time + 100, 'limit' => 5]);

    expect($filters(event(['created_at' => $time])))->toBeTrue();
    expect($filters(event(['created_at' => $time])))->toBeTrue();
    expect($filters(event(['created_at' => $time])))->toBeTrue();
    expect($filters(event(['created_at' => $time])))->toBeTrue();
    expect($filters(event(['created_at' => $time])))->toBeTrue();
    expect($filters(event(['created_at' => $time])))->toBeFalse();
});

it('handlers multiple filters', function() {
    $filters = Subscription::make(Condition::map(), ['#p' => ['RandomPTag']], ['#p' => ['RandomPTag2']]);

    expect($filters(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
    expect($filters(event(['tags' => [['p', 'RandomPTag2']]])))->toBeTrue();
});