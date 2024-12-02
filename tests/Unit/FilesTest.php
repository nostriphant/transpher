<?php

use nostriphant\Transpher\Files;

it('stores file, when event is in store', function () {
    $event_id = uniqid();

    $store = new nostriphant\Transpher\Stores\Memory([]);
    $store[$event_id] = \Pest\event(['id' => $event_id = uniqid()]);
    expect(isset($store[$event_id]))->toBeTrue();

    $files = new Files(ROOT_DIR . '/data/files/', $store);
    $file = tempnam(sys_get_temp_dir(), 'file');
    file_put_contents($file, uniqid());
    $hash = hash_file('sha256', $file);

    $files($hash)($event_id, 'file://' . $file);
    expect(ROOT_DIR . '/data/files/' . $hash)->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events')->toBeDirectory();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events/' . $event_id)->toBeFile();
});


it('ignores file, when event is NOT in store', function () {
    $event_id = uniqid();

    $store = new nostriphant\Transpher\Stores\Memory([]);

    $files = new Files(ROOT_DIR . '/data/files/', $store);
    $file = tempnam(sys_get_temp_dir(), 'file');
    file_put_contents($file, uniqid());
    $hash = hash_file('sha256', $file);

    $files($hash)($event_id, 'file://' . $file);
    expect(ROOT_DIR . '/data/files/' . $hash)->not()->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events/' . $event_id)->not()->toBeFile();
});
