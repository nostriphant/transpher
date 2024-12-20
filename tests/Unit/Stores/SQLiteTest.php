<?php

use nostriphant\Transpher\Stores\Engine\SQLite;

it('creates a table `event` if not exists', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new SQLite($sqlite, []);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    $expected_tables = ['event', 'tag', 'tag_value'];
    foreach ($expected_tables as $expected_table) {
        $table = $sqlite->querySingle("SELECT name FROM sqlite_schema WHERE type='table' AND name='{$expected_table}'");
        expect($table)->toBe($expected_table);
    }
});

it('retrieves an event with a tag', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new SQLite($sqlite, []);
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
    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    $event = $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'];
    expect($event->tags)->toBeIterable();
    expect($event->tags)->toHaveCount(1);
    expect($event->tags[0])->toBe(['p', 'first-value', 'second-value']);
});

it('stores an event with tags', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new SQLite($sqlite, []);
    expect($sqlite->lastErrorMsg())->toBe('not an error');

    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBeNull();
    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);
    expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);

    $event = \nostriphant\NIP01\Event::__set_state([
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

    $store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'] = $event;

    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');

    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(3);
    expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(3);
});



it('deletes an event with tags', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new SQLite($sqlite, []);
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

    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    unset($store['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']);

    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBeNull();
    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);
    expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'"))->toBe(0);
});

it('filters events', function (array $filter_prototype) {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new SQLite($sqlite, []);
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

    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    $statement = $sqlite->prepare("SELECT id, pubkey, created_at, kind, content, sig FROM event WHERE (id IN (?))");
    $statement->bindValue(1, '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');
    $result = $statement->execute();
    $data = $result->fetchArray();
    expect($data['id'])->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');

    $events = iterator_to_array(nostriphant\Transpher\Stores\Store::query($store, $filter_prototype));
    expect($sqlite->lastErrorMsg())->toBe('not an error');
    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(nostriphant\NIP01\Event::class);
    expect($events[0]->id)->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');
})->with([
    [['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb']]],
    [['authors' => ['a38bcec507900130fd6ec167dd7fa942014a92c07e56fe52e1fabfea14afcdfc']]],
    [['since' => 1731082480]],
    [['until' => 1731082500]],
    [['#e' => ['b9073d8a515eea632834db9f52d786882a90e7152601079dbec49f301e46bff9']]],
    [['#L' => ['pink.momostr']]],
    [['#k' => ['1']]]
]);

it('limits events in result set', function () {
    $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
    $sqlite = new SQLite3($db_file);
    expect($db_file)->toBeFile();

    $store = new SQLite($sqlite, []);
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

    $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'");

    $statement = $sqlite->prepare("SELECT id, pubkey, created_at, kind, content, sig FROM event WHERE (id IN (?))");
    $statement->bindValue(1, '07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');
    $result = $statement->execute();
    $data = $result->fetchArray();
    expect($data['id'])->toBe('07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb');

    $events = nostriphant\Transpher\Stores\Store::query($store, ['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'], 'limit' => 1]);
    expect(iterator_count($events))->toBe(1);

    $events = nostriphant\Transpher\Stores\Store::query($store, ['ids' => ['07cf455963bffe4ef851e4983df2d1495602714abc6c0e028c02752b16e11bcb'], 'limit' => 0]);
    expect(iterator_count($events))->toBe(0);
});
