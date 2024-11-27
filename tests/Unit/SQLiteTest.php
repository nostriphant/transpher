<?php

it('creates a table `event` if not exists', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\SQLite($sqlite);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    $expected_tables = ['event', 'tag', 'tag_value'];
    foreach ($expected_tables as $expected_table) {
        $table = $sqlite->querySingle("SELECT name FROM sqlite_schema WHERE type='table' AND name='{$expected_table}'");
        expect($table)->toBe($expected_table);
    }
});


it('can check if an event exists', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\SQLite($sqlite);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    $sqlite->exec("INSERT INTO event (id) VALUES ('2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0')");

    expect(isset($store['2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0']))->toBeTrue();
});
