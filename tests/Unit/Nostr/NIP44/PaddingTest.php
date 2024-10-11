<?php

use rikmeijer\Transpher\Nostr\NIP44\Padding;

require_once ROOT_DIR . '/tests/Unit/functions.php';


it('calcs padded length correctly', function () {
    foreach (vectors('nip44')->v2->valid->calc_padded_len as $vector) {
        expect(Padding::calculateLength($vector[0]))->toBe($vector[1]);
    }
});