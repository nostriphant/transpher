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

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();

    expect(isset($store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']))->toBeTrue();
});

it('can retrieve an event with tags added without a specific position', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\SQLite($sqlite);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();
    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'p')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'first-value')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'second-value')");

    $event = $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'];
    expect($event->tags)->toHaveCount(1);
    expect($event->tags[0])->toBe(['p', 'first-value', 'second-value']);
});

it('can retrieve an event with a tag', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\SQLite($sqlite);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();
    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'p')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, position, value) VALUES (1, 2, 'second-value')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, position, value) VALUES (1, 1, 'first-value')");

    $event = $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'];
    expect($event->tags)->toHaveCount(1);
    expect($event->tags[0])->toBe(['p', 'first-value', 'second-value']);
});


it('can retrieve an event with tags', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\SQLite($sqlite);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
            . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
            . "1731082493,"
            . "5,"
            . "'',"
            . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
            . ")"))->toBeTrue();

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'e')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'L')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (2, 'pink.momostr')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'k')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (3, '1')");

    $event = $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'];
    expect($event->pubkey)->toBe('a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc');
    expect($event->created_at)->toBe(1731082493);
    expect($event->kind)->toBe(5);
    expect($event->content)->toBe('');
    expect($event->sig)->toBe('ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44');
    expect($event->tags)->toHaveCount(3);
    expect($event->tags[0])->toBe(['e', 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9']);
    expect($event->tags[1])->toBe(['L', 'pink.momostr']);
    expect($event->tags[2])->toBe(['k', '1']);
});



it('can store an event with tags', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\SQLite($sqlite);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBeNull();
    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);
    expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);

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

    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');

    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(3);
    expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(3);
});


it('can delete an event with tags', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\SQLite($sqlite);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'e')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'L')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (2, 'pink.momostr')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'k')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (3, '1')");

    unset($store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']);

    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBeNull();
    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);
    expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);
});

it('can count events', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\SQLite($sqlite);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'e')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'L')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (2, 'pink.momostr')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'k')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (3, '1')");

    expect($store)->toHaveCount(1);
});

it('can filter an event on id', function (array $filter_prototype) {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new nostriphant\Transpher\SQLite($sqlite);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->exec("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                    . "'07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb',"
                    . "'a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc',"
                    . "1731082493,"
                    . "5,"
                    . "'',"
                    . "'ea4fbc932a5b1d9e68fa3deb3f7af83924c5b35871294a23a62f95fd33702e0bc701b10e1886811313007b42a7d5a5595d3eb8fb4980c24715fefc7632017d44'"
                    . ")"))->toBeTrue();

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'e')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (1, 'b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'L')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (2, 'pink.momostr')");

    $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb', 'k')");
    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES (3, '1')");

    $statement = $sqlite->prepare("SELECT id, pubkey, created_at, kind, content, sig FROM event WHERE (id IN (?))");
    $statement->bindValue(1, '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');
    $result = $statement->execute();
    $data = $result->fetchArray();
    expect($data['id'])->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');

    $events = iterator_to_array($store(\nostriphant\Transpher\Nostr\Subscription::make($filter_prototype)));
    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(nostriphant\NIP01\Event::class);
    expect($events[0]->id)->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');
})->with([
    [['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']]],
    [['authors' => ['a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc']]],
    [['since' => 1731082480]],
    [['until' => 1731082500]]
]);
