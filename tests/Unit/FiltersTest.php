<?php

use Transpher\Filters;

it('filters p-tags', function() {
    $filters = new Filters(['#p' => ['RandomPTag']]);
    $subscription = $filters();
    expect($subscription(['tags' => [['p', 'RandomPTag']]]))->toBeTrue();
});


it('filters e-tags', function() {
    $filters = new Filters(['#e' => ['RandomEventId']]);
    $subscription = $filters();
    expect($subscription(['tags' => [['e', 'RandomEventId']]]))->toBeTrue();
});

it('filters created since', function() {
    $time = time();
    $filters = new Filters(['since' => $time - 100]);
    $subscription = $filters();
    expect($subscription(['created_at' => $time]))->toBeTrue();
});

it('filters created until', function() {
    $time = time();
    $filters = new Filters(['until' => $time + 100]);
    $subscription = $filters();
    expect($subscription(['created_at' => $time]))->toBeTrue();
});


it('filters maximum number of items', function() {
    $time = time();
    $filters = new Filters(['until' => $time + 100, 'limit' => 5]);
    $subscription = $filters();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeFalse();
});