<?php

use rikmeijer\Transpher\Relay\Condition;
use rikmeijer\TranspherTests\Unit\Functions;

it('filters p-tags', function() {
    $filters = Condition::makeFiltersFromPrototypes(['#p' => ['RandomPTag']]);

    expect($filters(Functions::event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
});


it('filters e-tags', function() {
    $filters = Condition::makeFiltersFromPrototypes(['#e' => ['RandomEventId']]);

    expect($filters(Functions::event(['tags' => [['e', 'RandomEventId']]])))->toBeTrue();
});

it('filters created since', function() {
    $time = time();
    $filters = Condition::makeFiltersFromPrototypes(['since' => $time - 100]);

    expect($filters(Functions::event(['created_at' => $time])))->toBeTrue();
});

it('filters created until', function() {
    $time = time();
    $filters = Condition::makeFiltersFromPrototypes(['until' => $time + 100]);

    expect($filters(Functions::event(['created_at' => $time])))->toBeTrue();
});


it('filters maximum number of items', function() {
    $time = time();
    $filters = Condition::makeFiltersFromPrototypes(['until' => $time + 100, 'limit' => 5]);

    expect($filters(Functions::event(['created_at' => $time])))->toBeTrue();
    expect($filters(Functions::event(['created_at' => $time])))->toBeTrue();
    expect($filters(Functions::event(['created_at' => $time])))->toBeTrue();
    expect($filters(Functions::event(['created_at' => $time])))->toBeTrue();
    expect($filters(Functions::event(['created_at' => $time])))->toBeTrue();
    expect($filters(Functions::event(['created_at' => $time])))->toBeFalse();
});

it('handlers multiple filters', function() {
    $filters = Condition::makeFiltersFromPrototypes(['#p' => ['RandomPTag']], ['#p' => ['RandomPTag2']]);

    expect($filters(Functions::event(['tags' => [['p', 'RandomPTag']]])))->toBeTrue();
    expect($filters(Functions::event(['tags' => [['p', 'RandomPTag2']]])))->toBeTrue();
});