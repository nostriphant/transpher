<?php

it('can alternate execution paths', function () {
    $alternate = \nostriphant\Transpher\Alternate::first('Hello World');
    expect($alternate)->toHaveState(first: ['Hello World']);
});

it('ignores undefined execution paths', function () {
    $alternate = \nostriphant\Transpher\Alternate::second('Hello World');
    foreach ($alternate() as $msg) {
        
    }
})->throwsNoExceptions();


it('can fallback to a default execution paths', function () {
    $alternate = \nostriphant\Transpher\Alternate::first('Hello World');
    expect($alternate)->toHaveState(default: ['Hello World']);
});
