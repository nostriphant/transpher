<?php
use nostriphant\Transpher\Nostr\Subscription;

it('can retrieve events', function (array $filter_prototype, int $expected_count) {
    $store = new \nostriphant\Transpher\Stores\Memory([
        Pest\event(["content" => 'Hallo', "pubkey" => "2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0", 'kind' => 1]),
        Pest\event(["content" => 'Hallo 2', "pubkey" => "2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0", 'kind' => 2])
    ], Subscription::make([]));

    $events = $store(nostriphant\Transpher\Nostr\Subscription::make($filter_prototype));
    expect(iterator_count($events))->toBe($expected_count);
})->with([
    [['authors' => ["2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0"]], 2],
    [['authors' => ["2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0"], 'kinds' => [2]], 1]
]);

