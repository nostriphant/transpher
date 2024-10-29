<?php

use nostriphant\Transpher\Nostr\NIP44\Padding;
use function Pest\vectors;

it('calcs padded length correctly', function () {
    foreach (vectors('nip44')->v2->valid->calc_padded_len as $vector) {
        expect(Padding::calculateLength($vector[0]))->toBe($vector[1]);
    }
});