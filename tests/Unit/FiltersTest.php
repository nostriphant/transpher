<?php

use nostriphant\Transpher\Relay\Conditions;
use nostriphant\Transpher\Nostr\Subscription;
use function \Pest\event;

it('filters p-tags', function() {
    $subscription = Subscription::make(new Conditions(), ['#p' => ['RandomPTag']]);

    expect($subscription(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
});


it('filters e-tags', function() {
    $subscription = Subscription::make(new Conditions(), ['#e' => ['RandomEventId']]);

    expect($subscription(event(['tags' => [['e', 'RandomEventId']]])))->toBeTrue();
});

it('filters created since', function() {
    $time = time();
    $subscription = Subscription::make(new Conditions(), ['since' => $time - 100]);

    expect($subscription(event(['created_at' => $time])))->toBeTrue();
});

it('filters created until', function() {
    $time = time();
    $subscription = Subscription::make(new Conditions(), ['until' => $time + 100]);

    expect($subscription(event(['created_at' => $time])))->toBeTrue();
});


it('filters maximum number of items', function() {
    $time = time();
    $subscription = Subscription::make(new Conditions(), ['until' => $time + 100, 'limit' => 5]);

    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeFalse();
});

it('handlers multiple filters', function() {
    $subscription = Subscription::make(new Conditions(), ['#p' => ['RandomPTag']], ['#p' => ['RandomPTag2']]);

    expect($subscription(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
    expect($subscription(event(['tags' => [['p', 'RandomPTag2']]])))->toBeTrue();
});

it('treats unknown filters as tag', function () {
    $subscription = Subscription::make(new Conditions(), ["some-tag" => [1724755392]]);
    expect($subscription(event(['tags' => [['some-tag', 1724755392]]])))->toBeTrue();
});

it('ignores invalid expected values', function () {
    $subscription = Subscription::make(new Conditions(), ['#p' => 'RandomPTag']);

    expect($subscription(event(['tags' => [['p', 'blablabla']]])))->toBeTrue();
});
