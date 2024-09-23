<?php

it('generates a public key without an argument', function() {
    $private_key = \Transpher\Key::private('435790f13406085d153b10bd9e00a9f977e637f10ce37db5ccfc5d3440c12d6c');

    expect($private_key())->toBe('0389ac55aeeb301252da33b51ca4d189cb1d665b8f00618f5ea72c2ec59ca555e9');
});

it('signs a string with one argument', function() {
    $private_key = \Transpher\Key::private('435790f13406085d153b10bd9e00a9f977e637f10ce37db5ccfc5d3440c12d6c');

    expect($private_key())->toBe('0389ac55aeeb301252da33b51ca4d189cb1d665b8f00618f5ea72c2ec59ca555e9');
    
    $signature = $private_key(\Transpher\Key::signer('hallo world'));
    
    $reporting = set_error_handler(fn() => null);
    $verification = (new \Mdanter\Ecc\Crypto\Signature\SchnorrSignature())->verify('89ac55aeeb301252da33b51ca4d189cb1d665b8f00618f5ea72c2ec59ca555e9', $signature, 'hallo world');
    set_error_handler($reporting);
    
    expect($verification)->toBeTrue();
});