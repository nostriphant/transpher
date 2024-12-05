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

it('retrieves events', function (callable $factory, array $filter_prototype, int $expected_count) {
    list($store, $created_events) = $factory([],
            Pest\event(['id' => uniqid(), "content" => 'Hallo', "pubkey" => "2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0", 'kind' => 1]),
            Pest\event(['id' => '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', "content" => 'Hallo 2', "pubkey" => "2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0", 'kind' => 2])
    );

    $events = $store(nostriphant\Transpher\Nostr\Subscription::make($filter_prototype));
    expect(iterator_count($events()))->toBe($expected_count);
})->with('stores')->with([
    [['authors' => ["2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0"]], 2],
    [['authors' => ["2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0"], 'kinds' => [2]], 1]
]);

it('retrieves an event with tags', function (callable $factory) {
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

    $event = $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'];
    expect($event)->toBeInstanceOf(nostriphant\NIP01\Event::class);
    expect($event->pubkey)->toBe('a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc');
    expect($event->created_at)->toBe(1731082493);
    expect($event->kind)->toBe(5);
    expect($event->content)->toBe('');
    expect($event->sig)->toBe('ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44');
    expect($event->tags)->toHaveCount(3);

    expect(nostriphant\NIP01\Event::extractTagValues($event, 'e')[0])->toBe(['b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9']);
    expect(nostriphant\NIP01\Event::extractTagValues($event, 'L')[0])->toBe(['pink.momostr']);
    expect(nostriphant\NIP01\Event::extractTagValues($event, 'k')[0])->toBe(['1']);
})->with('stores');

it('ignores an event that does not matches whitelist filter', function (callable $factory) {
    list($store, $created_events) = $factory(['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']]);

    expect(isset($store['non-matching']))->toBeFalse();

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
    $store['non-matching'] = \nostriphant\NIP01\Event::__set_state([
        'id' => 'non-matching',
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

    expect(isset($store['non-matching']))->toBeFalse();
    expect(isset($store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']))->toBeTrue();
})->with('stores');

it('deletes events not matching whitelist filter', function (callable $factory) {
    list($store, $created_events) = $factory(['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']], \nostriphant\NIP01\Event::__set_state([
                'id' => 'non-matching',
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

    array_walk($created_events, fn(callable $is_deleted) => $is_deleted(true));
})->with('stores');


it('deletes no events when whitelist empty', function (callable $factory) {
    list($store, $created_events) = $factory([], \nostriphant\NIP01\Event::__set_state([
                'id' => 'non-matching',
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

    array_walk($created_events, fn(callable $is_deleted) => $is_deleted(false));
})->with('stores');
