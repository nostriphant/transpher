<?php

$logger = (require_once __DIR__ . '/bootstrap.php')('relay', 'INFO', $_SERVER['RELAY_LOG_LEVEL'] ?? 'INFO');

use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Key;

if (isset($_SERVER['RELAY_DATA'])) {
    $data_dir = $_SERVER['RELAY_DATA'];
    is_dir($data_dir) || mkdir($data_dir);

    $events = new nostriphant\Stores\Engine\SQLite(new SQLite3($data_dir . '/transpher.sqlite'));

    $store_path = $data_dir . '/events';
    if (is_dir($store_path)) {
        $logger->debug('Starting migrating events...');
        $logger->debug(\nostriphant\Stores\Engine\Disk::walk_store($store_path, function (nostriphant\NIP01\Event $event) use ($store_path, &$events) {
                    $events[$event->id] = $event;
                    return unlink($store_path . '/' . $event->id . '.php');
                }) . ' events migrated.');
    }

    $files_path = $data_dir . '/files';
} else {
    $store_path = $_SERVER['RELAY_STORE'] ?? ROOT_DIR . '/data/events';
    $events = new \nostriphant\Stores\Engine\Disk($store_path);

    $files_path = $_SERVER['RELAY_FILES'] ?? ROOT_DIR . '/data/files';
}

$whitelisted_pubkeys = [
    (new Bech32($_SERVER['RELAY_OWNER_NPUB']))(),
    Key::fromHex((new Bech32($_SERVER['AGENT_NSEC']))())(Key::public())
];

$follow_lists = nostriphant\Stores\Store::query($events, ['kinds' => [3], 'authors' => $whitelisted_pubkeys]);
foreach ($follow_lists as $follow_list) {
    $whitelisted_pubkeys = array_reduce($follow_list->tags, function (array $whitelisted_pubkeys, array $tag) {
        $whitelisted_pubkeys[] = $tag[1];
        return $whitelisted_pubkeys;
    }, $whitelisted_pubkeys);
}

$store = new nostriphant\Stores\Store($events, [
    ['authors' => $whitelisted_pubkeys],
    ['#p' => $whitelisted_pubkeys]
        ]);

$relay = new \nostriphant\Transpher\Amp\WebsocketServer($store, $files_path);

list($ip, $port) = explode(":", $_SERVER['argv'][1], 2);
$relay($ip, $port, $_SERVER['RELAY_MAX_CONNECTIONS_PER_IP'] ?? 1000, $logger, fn(int $signal) => $logger->info(sprintf("Received signal %d, stopping Relay server", $signal)));
