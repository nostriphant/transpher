<?php

use rikmeijer\Transpher\Nostr\Key;
use rikmeijer\Transpher\Nostr\NIP44;
use function \Pest\vectors;

it('generates a public key without an argument', function() {
    $private_key = Key::fromHex('435790f13406085d153b10bd9e00a9f977e637f10ce37db5ccfc5d3440c12d6c');

    expect($private_key(Key::public()))->toBe('89ac55aeeb301252da33b51ca4d189cb1d665b8f00618f5ea72c2ec59ca555e9');
});

it('converts between bytes, bech32 and hexidecimal', function() {
    
    $public_key_hex = '7e7e9c42a91bfef19fa929e5fda1b72e0ebc1a4c1141673e2794234d86addf4e';
    $public_key_bech32 = 'npub10elfcs4fr0l0r8af98jlmgdh9c8tcxjvz9qkw038js35mp4dma8qzvjptg';
    $private_key_hex = '67dea2ed018072d675f5415ecfaed7d2597555e202d85b3d65ea4e58d2d92ffa';
    $private_key_bech32 = 'nsec1vl029mgpspedva04g90vltkh6fvh240zqtv9k0t9af8935ke9laqsnlfe5';

    $key = Key::fromBech32($private_key_bech32);
    expect($key(fn() => func_get_arg(0)))->toBe($private_key_hex);
    expect($key(Key::public(\rikmeijer\Transpher\Nostr\Key\Format::BECH32)))->toBe($public_key_bech32);
    expect($key(Key::public(\rikmeijer\Transpher\Nostr\Key\Format::HEXIDECIMAL)))->toBe($public_key_hex);
    expect($key(Key::public(\rikmeijer\Transpher\Nostr\Key\Format::BINARY)))->toBe(hex2bin($public_key_hex));
});


it('shared_secret', function () {
    // https://github.com/paulmillr/noble-secp256k1/blob/main/test/wycheproof/ecdh_secp256k1_test.json
    foreach (vectors('ecdh-secp256k1')->testGroups[0]->tests as $vector) {
        if ($vector->result === 'valid') {
            $secret = Key::fromHex($vector->private)(Key::sharedSecret(substr($vector->public, 46)));
            expect(str_pad($secret, 64, '0', STR_PAD_LEFT))->toBe($vector->shared);
        }
    }
    
    
    $public_key_bech32 = 'npub1efz8l77esdtpw6l359sjvakm7azvyv6mkuxphjdk3vfzkgxkatrqlpf9s4';
    $public_key_hex = 'ca447ffbd98356176bf1a1612676dbf744c2335bb70c1bc9b68b122b20d6eac6';
    $private_key = Key::fromHex('56350645f60b55570901937c9196e618a5b87a2b64968b09c0b404efef74d2b5');
    
    $key = new NIP44\ConversationKey($private_key, hex2bin($public_key_hex));
    expect(''.$key)->not()->toBeEmpty();
    
});

it('signs a string with one argument', function() {
    $private_key = Key::fromHex('435790f13406085d153b10bd9e00a9f977e637f10ce37db5ccfc5d3440c12d6c');

    expect($private_key(Key::public()))->toBe('89ac55aeeb301252da33b51ca4d189cb1d665b8f00618f5ea72c2ec59ca555e9');
    
    $signature = $private_key(Key::signer('hallo world'));
    
    $reporting = set_error_handler(fn() => null);
    $verification = (new \Mdanter\Ecc\Crypto\Signature\SchnorrSignature())->verify('89ac55aeeb301252da33b51ca4d189cb1d665b8f00618f5ea72c2ec59ca555e9', $signature, 'hallo world');
    set_error_handler($reporting);
    
    expect($verification)->toBeTrue();
});

it('varifies a signature', function () {
    $private_key = Key::fromHex('435790f13406085d153b10bd9e00a9f977e637f10ce37db5ccfc5d3440c12d6c');

    expect($private_key(Key::public()))->toBe('89ac55aeeb301252da33b51ca4d189cb1d665b8f00618f5ea72c2ec59ca555e9');

    $signature = $private_key(Key::signer('hallo world'));

    expect(Key::verify('89ac55aeeb301252da33b51ca4d189cb1d665b8f00618f5ea72c2ec59ca555e9', $signature, 'hallo world'))->toBeTrue();
});
