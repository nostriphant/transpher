<?php

use Transpher\Filters;

it('filters p-tags', function() {
    $subscription = Filters::constructFromPrototype(['#p' => ['RandomPTag']]);
    expect($subscription(['tags' => [['p', 'RandomPTag']]]))->toBeTrue();
});


it('filters e-tags', function() {
    $subscription = Filters::constructFromPrototype(['#e' => ['RandomEventId']]);
    expect($subscription(['tags' => [['e', 'RandomEventId']]]))->toBeTrue();
});

it('filters created since', function() {
    $time = time();
    $subscription = Filters::constructFromPrototype(['since' => $time - 100]);
    expect($subscription(['created_at' => $time]))->toBeTrue();
});

it('filters created until', function() {
    $time = time();
    $subscription = Filters::constructFromPrototype(['until' => $time + 100]);
    expect($subscription(['created_at' => $time]))->toBeTrue();
});


it('filters maximum number of items', function() {
    $time = time();
    $subscription = Filters::constructFromPrototype(['until' => $time + 100, 'limit' => 5]);
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeFalse();
});