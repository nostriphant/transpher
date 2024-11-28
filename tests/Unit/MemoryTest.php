<?php

it('can retrieve events', function (array $filter_prototype, int $expected_count) {
    $store = \Pest\store([
            Pest\event(["content" => 'Hallo', 'kind' => 1]),
        Pest\event(["content" => 'Hallo 2', 'kind' => 2])
    ]);

    $events = $store(nostriphant\Transpher\Nostr\Subscription::make($filter_prototype));

    expect(iterator_count($events))->toBe($expected_count);
})->with([
    [['authors' => [Pest\pubkey_recipient()]], 2],
    [['authors' => [Pest\pubkey_recipient()], 'kinds' => [2]], 1]
]);

