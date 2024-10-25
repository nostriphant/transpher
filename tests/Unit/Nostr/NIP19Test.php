<?php

use rikmeijer\Transpher\Nostr\Bech32;

it('converts between bech32 and hexidecimal', function () {
    $public_key_hex = '7e7e9c42a91bfef19fa929e5fda1b72e0ebc1a4c1141673e2794234d86addf4e';
    $public_key_bech32 = 'npub10elfcs4fr0l0r8af98jlmgdh9c8tcxjvz9qkw038js35mp4dma8qzvjptg';
    $private_key_hex = '67dea2ed018072d675f5415ecfaed7d2597555e202d85b3d65ea4e58d2d92ffa';
    $private_key_bech32 = 'nsec1vl029mgpspedva04g90vltkh6fvh240zqtv9k0t9af8935ke9laqsnlfe5';

    expect(Bech32::fromNpub($public_key_bech32))->toBe($public_key_hex);
    expect(Bech32::toNpub($public_key_hex))->toBe($public_key_bech32);

    expect(Bech32::fromNsec($private_key_bech32))->toBe($private_key_hex);
    expect(Bech32::toNsec($private_key_hex))->toBe($private_key_bech32);
});
