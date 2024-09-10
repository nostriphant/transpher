<?php

use Transpher\Filters;

it('filter tags', function() {
    $subscription = Filters::constructSubscription(['#p' => ['RandomPTag']]);
    expect($subscription(['tags' => [['p', 'RandomPTag']]]))->toBeTrue();
});