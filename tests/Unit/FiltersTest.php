<?php

use Transpher\Filters;

it('filters p-tags', function() {
    $subscription = new Filters(['#p' => ['RandomPTag']]);
    
    expect($subscription(['tags' => [['p', 'RandomPTag']]]))->toBeTrue();
});


it('filters e-tags', function() {
    $subscription = new Filters(['#e' => ['RandomEventId']]);
    
    expect($subscription(['tags' => [['e', 'RandomEventId']]]))->toBeTrue();
});

it('filters created since', function() {
    $time = time();
    $subscription = new Filters(['since' => $time - 100]);
    
    expect($subscription(['created_at' => $time]))->toBeTrue();
});

it('filters created until', function() {
    $time = time();
    $subscription = new Filters(['until' => $time + 100]);
    
    expect($subscription(['created_at' => $time]))->toBeTrue();
});


it('filters maximum number of items', function() {
    $time = time();
    $subscription = new Filters(['until' => $time + 100, 'limit' => 5]);
    
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeTrue();
    expect($subscription(['created_at' => $time]))->toBeFalse();
});