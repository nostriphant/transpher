<?php
use nostriphant\Transpher\Nostr\Subscription;

it('checks if an event exists', function (callable $factory) {
    list($store, ) = $factory([], \nostriphant\NIP01\Event::__set_state([
                'id' => '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',
                'pubkey' => 'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',
                'created_at' => 1731082493,
                'kind' => 5,
                'content' => '',
                'sig' => 'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44',
                'tags' => [
                    0 => [
                        0 => 'e',
                        1 => 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9',
                    ],
                    1 => [
                        0 => 'L',
                        1 => 'pink.momostr',
                    ],
                    2 => [
                        0 => 'k',
                        1 => '1',
                    ],
                ],
    ]));

    expect(isset($store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']))->toBeTrue();
})->with('stores');

it('ignores an event that matches ignore filter', function (callable $factory) {
    list($store, $created_events) = $factory(['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']]);

    expect(isset($store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']))->toBeFalse();
    
    $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'] = \nostriphant\NIP01\Event::__set_state([
        'id' => '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',
        'pubkey' => 'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',
        'created_at' => 1731082493,
        'kind' => 5,
        'content' => '',
        'sig' => 'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44',
        'tags' => [
            0 => [
                0 => 'e',
                1 => 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9',
            ],
            1 => [
                0 => 'L',
                1 => 'pink.momostr',
            ],
            2 => [
                0 => 'k',
                1 => '1',
            ],
        ],
    ]);

    expect(isset($store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']))->toBeFalse();
})->with('stores');

it('deletes events matching ignore filter', function (callable $factory) {
    list($store, $created_events) = $factory(['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']], \nostriphant\NIP01\Event::__set_state([
                'id' => '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',
                'pubkey' => 'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',
                'created_at' => 1731082493,
                'kind' => 5,
                'content' => '',
                'sig' => 'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44',
                'tags' => [
                    0 => [
                        0 => 'e',
                        1 => 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9',
                    ],
                    1 => [
                        0 => 'L',
                        1 => 'pink.momostr',
                    ],
                    2 => [
                        0 => 'k',
                        1 => '1',
                    ],
                ],
    ]));


    array_walk($created_events, fn(callable $is_deleted) => $is_deleted());
})->with('stores');
