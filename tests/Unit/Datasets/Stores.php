<?php

use nostriphant\Transpher\Nostr\Subscription;

dataset('stores', [
    'disk' => function (array $whitelist_prototype, nostriphant\NIP01\Event ...$events): array {
        $transpher_store = ROOT_DIR . '/data/disktest_' . uniqid();
        expect($transpher_store)->not()->toBeDirectory();

        new \nostriphant\Transpher\Stores\Disk($transpher_store, []);
        expect($transpher_store)->toBeDirectory();

        $created_events = [];
        foreach ($events as $event) {
            file_put_contents($transpher_store . DIRECTORY_SEPARATOR . $event->id . '.php', '<?php return ' . var_export($event, true) . ';');
            $created_events[$event->id] = fn(bool $is_deleted) => expect(is_file($transpher_store . DIRECTORY_SEPARATOR . $event->id . '.php'))->toBe(!$is_deleted);
        }

        return [new \nostriphant\Transpher\Stores\Disk($transpher_store, [$whitelist_prototype]), $created_events];
    },
    'sqlite' => function (array $whitelist_prototype, nostriphant\NIP01\Event ...$events): array {
        $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
        $sqlite = new SQLite3($db_file);
        expect($db_file)->toBeFile();

        new \nostriphant\Transpher\Stores\SQLite($sqlite, []);
        expect($sqlite->lastErrorMsg())->toBe('not an error');

        $created_events = [];
        foreach ($events as $event) {
            expect($sqlite->exec($query = "INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                            . "'{$event->id}',"
                            . "'{$event->pubkey}',"
                            . "{$event->created_at},"
                            . "{$event->kind},"
                            . "'{$event->content}',"
                            . "'{$event->sig}'"
                            . ")"))->toBeTrue($sqlite->lastErrorMsg() . ' in ' . $query);

            foreach ($event->tags as $tag) {
                $sqlite->exec("INSERT INTO tag (event_id, name) VALUES ('{$event->id}', '{$tag[0]}')");
                $tag_id = $sqlite->lastInsertRowID();
                foreach (array_slice($tag, 1) as $value) {
                    $sqlite->exec("INSERT INTO tag_value (tag_id, value) VALUES ({$tag_id}, '{$value}')");
                }
            }

            $sqlite->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = '{$event->id}'");


            expect($sqlite->querySingle("SELECT id FROM event WHERE id = '{$event->id}'"))->toBe($event->id);
            expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '{$event->id}'"))->toBe(count($event->tags));

            $created_events[$event->id] = function (bool $is_deleted) use ($sqlite, $event) {
                if ($is_deleted) {
                    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '{$event->id}'"))->toBeNull();
                    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '{$event->id}'"))->toBe(0);
                    expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '{$event->id}'"))->toBe(0);
                } else {
                    expect($sqlite->querySingle("SELECT id FROM event WHERE id = '{$event->id}'"))->toBe($event->id);
                    expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '{$event->id}'"))->toBe(count($event->tags));
                }
            };
        }


        $store = new \nostriphant\Transpher\Stores\SQLite($sqlite, [$whitelist_prototype]);
        expect($sqlite->lastErrorMsg())->toBe('not an error');

        return [$store, $created_events];
    },
    'memory' => function (array $whitelist_prototype, nostriphant\NIP01\Event ...$events): array {
        $created_events = [];
        foreach ($events as $event) {
            $created_events[$event->id] = $event;
        }

        $store = new \nostriphant\Transpher\Stores\Memory($created_events, [$whitelist_prototype]);

        return [$store, array_map(fn(nostriphant\NIP01\Event $event) => fn(bool $is_deleted) => expect(isset($store[$event->id]))->toBe($is_deleted === false), $created_events)];
    }
]);
