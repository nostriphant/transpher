<?php

use rikmeijer\Transpher\Relay\Subscriptions;
use rikmeijer\Transpher\Nostr\Filters;
use rikmeijer\Transpher\Relay\Condition;

it('adds and  removes a subscription from the subscriptions-closure', function() {
    $subscriptions = new Subscriptions();
    $refl_subscriptions = (new ReflectionObject($subscriptions));
    $original_count = count($refl_subscriptions->getStaticPropertyValue('subscriptions'));

    $relayer = Mockery::mock(\rikmeijer\Transpher\Relay\Sender::class)->allows([
            '__invoke' => true
    ]);
    
    Subscriptions::subscribe($relayer, 'my-awesome-subscription', Filters::make(Condition::map(), ['ids' => []]));
    expect($refl_subscriptions->getStaticPropertyValue('subscriptions'))->toHaveCount($original_count + 1);

    Subscriptions::unsubscribe('my-missing-subscription');
    expect($refl_subscriptions->getStaticPropertyValue('subscriptions'))->toHaveCount($original_count + 1);

    Subscriptions::unsubscribe('my-awesome-subscription');
    expect($refl_subscriptions->getStaticPropertyValue('subscriptions'))->toHaveCount($original_count);
});