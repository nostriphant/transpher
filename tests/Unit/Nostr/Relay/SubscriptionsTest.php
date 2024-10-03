<?php

use Transpher\Nostr\Relay\Subscriptions;
use Transpher\Nostr\Relay\Subscriptions\Subscribe;
use Transpher\Nostr\Relay\Subscriptions\Unsubscribe;

it('adds and  removes a subscription from the subscriptions-closure', function() {
    $refl_subscriptions = (new ReflectionObject(Subscriptions::makeStore()));
    expect(Subscriptions::makeStore())->toBeInstanceOf(Subscriptions::class);
    expect($refl_subscriptions->getProperty('subscriptions')->getValue(Subscriptions::makeStore()))->toHaveCount(0);
    
    Subscriptions::subscribe('my-awesome-subscription', fn() => true, function(string $subscriptionId, array $event) : bool {
        return true;
    });
    expect($refl_subscriptions->getProperty('subscriptions')->getValue(Subscriptions::makeStore()))->toHaveCount(1);
    
    Subscriptions::unsubscribe('my-missing-subscription');
    expect($refl_subscriptions->getProperty('subscriptions')->getValue(Subscriptions::makeStore()))->toHaveCount(1);
    
    
    Subscriptions::unsubscribe('my-awesome-subscription');
    expect($refl_subscriptions->getProperty('subscriptions')->getValue(Subscriptions::makeStore()))->toHaveCount(0);
    
});