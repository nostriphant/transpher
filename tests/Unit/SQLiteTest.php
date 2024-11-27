<?php

it('creates a table `event` if not exists', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\SQLite($sqlite);

    $tables = $sqlite->query("SELECT name FROM sqlite_schema WHERE type='table'");

    $expected_tables = ['event', 'tag'];
    while ($table = $tables->fetchArray()) {
        expect($table['name'])->toBeIn($expected_tables);
    }
});
