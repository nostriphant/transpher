<?php

use rikmeijer\Transpher\Nostr\NIP44\Padding;
use rikmeijer\TranspherTests\Unit\Functions;

it('calcs padded length correctly', function () {
    foreach (Functions::vectors('nip44')->v2->valid->calc_padded_len as $vector) {
        expect(Padding::calculateLength($vector[0]))->toBe($vector[1]);
    }
});