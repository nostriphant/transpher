<?php

use nostriphant\NIP01Tests\Functions as NIP01TestFunctions;
use nostriphant\Transpher\Relay\Files;

it('stores file, when event is in store', function () {
    $event_id = uniqid();

    $store = \Pest\store();
    $store[$event_id] = NIP01TestFunctions::event(['id' => $event_id = uniqid()]);
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

    $store = \Pest\store();

    $files = new Files(ROOT_DIR . '/data/files/', $store);
    $file = tempnam(sys_get_temp_dir(), 'file');
    file_put_contents($file, uniqid());
    $hash = hash_file('sha256', $file);

    $files($hash)($event_id, 'file://' . $file);
    expect(ROOT_DIR . '/data/files/' . $hash)->not()->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events/' . $event_id)->not()->toBeFile();
});


it('removes files, when no events directory', function () {
    $store = \Pest\store();

    $hash = uniqid();

    expect(ROOT_DIR . '/data/files/' . $hash . '.events')->not()->toBeDirectory();

    file_put_contents(ROOT_DIR . '/data/files/' . $hash, uniqid());
    mkdir(ROOT_DIR . '/data/files/' . $hash . '.events');
    expect(ROOT_DIR . '/data/files/' . $hash)->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events')->toBeDirectory();

    $files = new Files(ROOT_DIR . '/data/files/', $store);

    expect(ROOT_DIR . '/data/files/' . $hash)->not()->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events')->not()->toBeDirectory();
});

it('removes files, when no events in events directory exist', function () {
    $store = \Pest\store();

    $hash = uniqid();
    file_put_contents(ROOT_DIR . '/data/files/' . $hash, uniqid());
    expect(ROOT_DIR . '/data/files/' . $hash)->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events')->not()->toBeDirectory();

    $files = new Files(ROOT_DIR . '/data/files/', $store);

    expect(ROOT_DIR . '/data/files/' . $hash)->not()->toBeFile();
});

it('removes files, when event is NOT in store', function () {
    $event_id = uniqid();

    $store = \Pest\store();

    $hash = uniqid();
    file_put_contents(ROOT_DIR . '/data/files/' . $hash, uniqid());
    mkdir(ROOT_DIR . '/data/files/' . $hash . '.events');
    touch(ROOT_DIR . '/data/files/' . $hash . '.events/' . $event_id);
    expect(ROOT_DIR . '/data/files/' . $hash)->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events')->toBeDirectory();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events/' . $event_id)->toBeFile();

    $files = new Files(ROOT_DIR . '/data/files/', $store);

    expect(ROOT_DIR . '/data/files/' . $hash . '.events/' . $event_id)->not()->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash)->not()->toBeFile();
});
