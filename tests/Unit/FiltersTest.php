<?php

use function \Pest\event;

function subscription(array $filter_prototypes) {
    return \nostriphant\Transpher\Relay\Condition::makeConditions($filter_prototypes);
}

it('filters p-tags', function () {
    $subscription = subscription([['#p' => ['RandomPTag']]]);

    expect($subscription(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
});

it('filters e-tags', function() {
    $subscription = subscription([['#e' => ['RandomEventId']]]);

    expect($subscription(event(['tags' => [['e', 'RandomEventId']]])))->toBeTrue();
});

it('filters created since', function() {
    $time = time();
    $subscription = subscription([['since' => $time - 100]]);

    expect($subscription(event(['created_at' => $time])))->toBeTrue();
});

it('filters created until', function() {
    $time = time();
    $subscription = subscription([['until' => $time + 100]]);

    expect($subscription(event(['created_at' => $time])))->toBeTrue();
});


it('filters maximum number of items', function() {
    $time = time();
    $subscription = subscription([['until' => $time + 100, 'limit' => 5]]);

    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeFalse();
});

it('handlers multiple filters', function() {
    $subscription = subscription([['#p' => ['RandomPTag']], ['#p' => ['RandomPTag2']]]);

    expect($subscription(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
    expect($subscription(event(['tags' => [['p', 'RandomPTag2']]])))->toBeTrue();
});

it('treats unknown filters as tag', function () {
    $subscription = subscription([["some-tag" => [1724755392]]]);
    expect($subscription(event(['tags' => [['some-tag', 1724755392]]])))->toBeTrue();
});

it('ignores invalid expected values', function () {
    $subscription = subscription([['#p' => 'RandomPTag']]);

    expect($subscription(event(['tags' => [['p', 'blablabla']]])))->toBeTrue();
});
