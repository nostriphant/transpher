<?php

use rikmeijer\Transpher\Relay\Filter;
use rikmeijer\Transpher\Nostr\Event;

function event(array $event) : Event {
    return new Event(...array_merge([
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
    $subscription = Filter::make(['#p' => ['RandomPTag']]);
    
    expect($subscription(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
});


it('filters e-tags', function() {
    $subscription = Filter::make(['#e' => ['RandomEventId']]);
    
    expect($subscription(event(['tags' => [['e', 'RandomEventId']]])))->toBeTrue();
});

it('filters created since', function() {
    $time = time();
    $subscription = Filter::make(['since' => $time - 100]);
    
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
});

it('filters created until', function() {
    $time = time();
    $subscription = Filter::make(['until' => $time + 100]);
    
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
});


it('filters maximum number of items', function() {
    $time = time();
    $subscription = Filter::make(['until' => $time + 100, 'limit' => 5]);
    
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeTrue();
    expect($subscription(event(['created_at' => $time])))->toBeFalse();
});

it('handlers multiple filters', function() {
    $subscription = Filter::make(['#p' => ['RandomPTag']], ['#p' => ['RandomPTag2']]);
    
    expect($subscription(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
    expect($subscription(event(['tags' => [['p', 'RandomPTag2']]])))->toBeTrue();
});