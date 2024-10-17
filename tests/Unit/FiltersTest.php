<?php

use rikmeijer\Transpher\Relay\Filters;
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
    $filters = Filters::make(['#p' => ['RandomPTag']]);

    expect($filters(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
});


it('filters e-tags', function() {
    $filters = Filters::make(['#e' => ['RandomEventId']]);

    expect($filters(event(['tags' => [['e', 'RandomEventId']]])))->toBeTrue();
});

it('filters created since', function() {
    $time = time();
    $filters = Filters::make(['since' => $time - 100]);

    expect($filters(event(['created_at' => $time])))->toBeTrue();
});

it('filters created until', function() {
    $time = time();
    $filters = Filters::make(['until' => $time + 100]);

    expect($filters(event(['created_at' => $time])))->toBeTrue();
});


it('filters maximum number of items', function() {
    $time = time();
    $filters = Filters::make(['until' => $time + 100, 'limit' => 5]);

    expect($filters(event(['created_at' => $time])))->toBeTrue();
    expect($filters(event(['created_at' => $time])))->toBeTrue();
    expect($filters(event(['created_at' => $time])))->toBeTrue();
    expect($filters(event(['created_at' => $time])))->toBeTrue();
    expect($filters(event(['created_at' => $time])))->toBeTrue();
    expect($filters(event(['created_at' => $time])))->toBeFalse();
});

it('handlers multiple filters', function() {
    $filters = Filters::make(['#p' => ['RandomPTag']], ['#p' => ['RandomPTag2']]);

    expect($filters(event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
    expect($filters(event(['tags' => [['p', 'RandomPTag2']]])))->toBeTrue();
});