<?php

use Transpher\Filters;
use Transpher\Nostr\Event\Signed;

function event(array $event) : Signed {
    return Signed::import(array_merge([
        'id' => '',
        'pubkey' => '',
        'created_at' => time(),
        'kind' => 1,
        'content' => 'Hello World',
        'sig' => '',
        'tags' => []
    ], $event));
}

it('filters p-tags', function() {
    $subscription = new Filters(['#p' => ['RandomPTag']]);
    
    expect($subscription(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
});


it('filters e-tags', function() {
    $subscription = new Filters(['#e' => ['RandomEventId']]);
    
    expect($subscription(event(['tags' => [['e', 'RandomEventId']]])))->toBeTrue();
});

it('filters created since', function() {
    $time = time();
    $subscription = new Filters(['since' => $time - 100]);
    
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
});

it('filters created until', function() {
    $time = time();
    $subscription = new Filters(['until' => $time + 100]);
    
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
});


it('filters maximum number of items', function() {
    $time = time();
    $subscription = new Filters(['until' => $time + 100, 'limit' => 5]);
    
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeFalse();
});