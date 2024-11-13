<?php

use nostriphant\Transpher\Relay\Incoming\Req\Limits;
use nostriphant\Transpher\Relay\Incoming\Constraint\Result;

it('has a maximum number of filters per subscription.', function () {
    $limits = Limits::construct(max_filters_per_subscription: 1);

    $limit = $limits([['ids' => ['a']]]);
    expect($limit)->toHaveState(Result::ACCEPTED);

    $limit = $limits([['ids' => ['a']], ['ids' => ['a']]]);
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(...\Pest\rejected('max number of filters per subscription (1) reached'));
});

it('has a maximum number of filters per subscription. Defaults to 10.', function () {
    

    $limits = Limits::construct();

    $limit = $limits([
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
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(...\Pest\rejected('max number of filters per subscription (10) reached'));
});


it('has a maximum number of filters per subscription. Disabled when set to zero.', function () {
    

    $limits = Limits::construct(max_filters_per_subscription: 0);

    $limit = $limits([['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']], ['ids' => ['a']]]);
    expect($limit)->toHaveState(Result::ACCEPTED);
});

it('has a maximum number of filters per subscription, configurable through env-vars. Defaults to 10. Disabled when set to zero.', function () {
    

    putenv('LIMIT_REQ_MAX_FILTERS_PER_SUBSCRIPTION=1');
    $limits = Limits::fromEnv();

    $limit = $limits([['ids' => ['a']]]);
    expect($limit)->toHaveState(Result::ACCEPTED);

    $limit = $limits([['ids' => ['a']], ['ids' => ['a']]]);
    expect($limit)->toHaveState(Result::REJECTED);
    $limit(...\Pest\rejected('max number of filters per subscription (1) reached'));
    putenv('LIMIT_REQ_MAX_FILTERS_PER_SUBSCRIPTION');
});
