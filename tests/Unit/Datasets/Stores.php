<?php

use nostriphant\Transpher\Nostr\Subscription;

dataset('stores', [
    'disk' => function (array $ignore_prototype, nostriphant\NIP01\Event ...$events): array {
        $transpher_store = ROOT_DIR . '/data/disktest_' . uniqid();
        expect($transpher_store)->not()->toBeDirectory();

        new \nostriphant\Transpher\Stores\Disk($transpher_store, Subscription::make([]));
        expect($transpher_store)->toBeDirectory();

        $created_events = [];
        foreach ($events as $event) {
            file_put_contents($transpher_store . DIRECTORY_SEPARATOR . $event->id . '.php', '<?php return ' . var_export($event, true) . ';');
            $created_events[] = fn() => expect($transpher_store . DIRECTORY_SEPARATOR . $event->id . '.php')->not()->toBeFile();
        }

        return [new \nostriphant\Transpher\Stores\Disk($transpher_store, Subscription::make($ignore_prototype)), $created_events];
    },
    'sqlite' => function (array $ignore_prototype, nostriphant\NIP01\Event ...$events): array {
        $db_file = tempnam(sys_get_temp_dir(), 'test') . '.sqlite';
        $sqlite = new SQLite3($db_file);
        expect($db_file)->toBeFile();

        new \nostriphant\Transpher\Stores\SQLite($sqlite, Subscription::make([]), Mockery::spy(\Psr\Log\LoggerInterface::class));
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

            $created_events[] = function () use ($sqlite, $event) {
                expect($sqlite->querySingle("SELECT id FROM event WHERE id = '{$event->id}'"))->toBeNull();
                expect($sqlite->querySingle("SELECT COUNT(id) FROM tag WHERE event_id = '{$event->id}'"))->toBe(0);
                expect($sqlite->querySingle("SELECT COUNT(tag_value.id) FROM tag LEFT JOIN tag_value ON tag.id = tag_value.tag_id WHERE tag.event_id = '{$event->id}'"))->toBe(0);
            };
        }


        $store = new \nostriphant\Transpher\Stores\SQLite($sqlite, Subscription::make($ignore_prototype), Mockery::spy(\Psr\Log\LoggerInterface::class));
        expect($sqlite->lastErrorMsg())->toBe('not an error');

        return [$store, $created_events];
    },
    'memory' => function (array $ignore_prototype, nostriphant\NIP01\Event ...$events): array {
        $created_events = [];
        foreach ($events as $event) {
            $created_events[$event->id] = $event;
        }

        $store = new \nostriphant\Transpher\Stores\Memory($created_events, Subscription::make($ignore_prototype));

        return [$store, array_map(fn(nostriphant\NIP01\Event $event) => fn() => expect(isset($store[$event->id]))->toBeFalse(), $created_events)];
    }
]);
