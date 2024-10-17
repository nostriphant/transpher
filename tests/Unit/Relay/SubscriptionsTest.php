<?php

use rikmeijer\Transpher\Relay\Subscriptions;
use rikmeijer\Transpher\Relay\Filter;

it('adds and  removes a subscription from the subscriptions-closure', function() {
    $subscriptions = new Subscriptions();
    $refl_subscriptions = (new ReflectionObject($subscriptions));
    expect($refl_subscriptions->getStaticPropertyValue('subscriptions'))->toHaveCount(0);
    
    $relayer = Mockery::mock(\rikmeijer\Transpher\Relay\Sender::class)->allows([
            '__invoke' => true
    ]);
    
    Subscriptions::subscribe($relayer, 'my-awesome-subscription', Filter::make(['id' => '']));
    expect($refl_subscriptions->getStaticPropertyValue('subscriptions'))->toHaveCount(1);
    
    Subscriptions::unsubscribe('my-missing-subscription');
    expect($refl_subscriptions->getStaticPropertyValue('subscriptions'))->toHaveCount(1);
    
    Subscriptions::unsubscribe('my-awesome-subscription');
    expect($refl_subscriptions->getStaticPropertyValue('subscriptions'))->toHaveCount(0);
    
});