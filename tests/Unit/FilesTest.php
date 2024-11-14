<?php

use nostriphant\Transpher\Files;

it('stores files', function () {
    $files = new Files(ROOT_DIR . '/data/files/');
    $file = tempnam(sys_get_temp_dir(), 'file');
    file_put_contents($file, uniqid());
    $hash = hash_file('sha256', $file);

    $files($hash)($event_id = uniqid(), 'file://' . $file);
    expect(ROOT_DIR . '/data/files/' . $hash)->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events')->toBeDirectory();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events/' . $event_id)->toBeFile();
});
