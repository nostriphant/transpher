<?php

use nostriphant\Transpher\Relay\Incoming\Req\Limits;
use nostriphant\Transpher\Relay\Incoming\Constraint\Result;

it('has a maximum number of filters per subscription.', function () {
    $subscriptions = \Pest\subscriptions();

    $limits = Limits::construct(max_filters_per_subscription: 1);

    $limit = $limits($subscriptions, [['ids' => ['a']]]);
    expect($limit->result)->toBe(Result::ACCEPTED, $limit->reason ?? '');

    $limit = $limits($subscriptions, [['ids' => ['a']], ['ids' => ['a']]]);
    expect($limit->result)->toBe(Result::REJECTED);
    expect($limit->reason)->toBe('max number of filters per subscription (1) reached');
});

it('has a maximum number of filters per subscription. Defaults to 10.', function () {
    $subscriptions = \Pest\subscriptions();

    $limits = Limits::construct();

    $limit = $limits($subscriptions, [
        ['ids' => ['a']],
        ['ids' => ['a']],
        ['ids' => ['a']],
        ['ids' => ['a']],
        ['ids' => ['a']],
        ['ids' => ['a']],
        ['ids' => ['a']],
        ['ids' => ['a']],
        ['ids' => ['a']],
        ['ids' => ['a']],
        ['ids' => ['a']]
    ]);
    expect($limit->result)->toBe(Result::REJECTED);
    expect($limit->reason)->toBe('max number of filters per subscription (10) reached');
});


it('has a maximum number of filters per subscription. Disabled when set to zero.', function () {
    $subscriptions = \Pest\subscriptions();

    $limits = Limits::construct(max_filters_per_subscription: 0);

    $limit = $limits($subscriptions, [['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']]]);
    expect($limit->result)->toBe(Result::ACCEPTED, $limit->reason ?? '');
});

it('has a maximum number of filters per subscription, configurable through env-vars. Defaults to 10. Disabled when set to zero.', function () {
    $subscriptions = \Pest\subscriptions();

    putenv('LIMIT_REQ_MAX_FILTERS_PER_SUBSCRIPTION=1');
    $limits = Limits::fromEnv();

    $limit = $limits($subscriptions, [['ids' => ['a']]]);
    expect($limit->result)->toBe(Result::ACCEPTED, $limit->reason ?? '');

    $limit = $limits($subscriptions, [['ids' => ['a']], ['ids' => ['a']]]);
    expect($limit->result)->toBe(Result::REJECTED);
    expect($limit->reason)->toBe('max number of filters per subscription (1) reached');
    putenv('LIMIT_REQ_MAX_FILTERS_PER_SUBSCRIPTION');
});
