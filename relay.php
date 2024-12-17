<?php

$logger = (require_once __DIR__ . '/bootstrap.php')('relay', 'INFO', $_SERVER['RELAY_LOG_LEVEL'] ?? 'INFO');

use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Key;

if (isset($_SERVER['RELAY_DATA'])) {
    $data_dir = $_SERVER['RELAY_DATA'];
    is_dir($data_dir) || mkdir($data_dir);

    $events = new nostriphant\Transpher\Stores\Engine\SQLite(new SQLite3($data_dir . '/transpher.sqlite'));

    $store_path = $data_dir . '/events';
    if (is_dir($store_path)) {
        $logger->debug('Starting migrating events...');
        $logger->debug(\nostriphant\Transpher\Stores\Engine\Disk::walk_store($store_path, function (nostriphant\NIP01\Event $event) use ($store_path, &$events) {
                    $events[$event->id] = $event;
                    return unlink($store_path . '/' . $event->id . '.php');
                }) . ' events migrated.');
    }

    $files_path = $data_dir . '/files';
} else {
    $store_path = $_SERVER['RELAY_STORE'] ?? ROOT_DIR . '/data/events';
    $events = new \nostriphant\Transpher\Stores\Engine\Disk($store_path);

    $files_path = $_SERVER['RELAY_FILES'] ?? ROOT_DIR . '/data/files';
}

$pubkey_owner = (new Bech32($_SERVER['RELAY_OWNER_NPUB']))();
$whitelist_prototypes = [
    [
        'authors' => [
            $pubkey_owner,
            Key::fromHex((new Bech32($_SERVER['AGENT_NSEC']))())(Key::public())
        ],
    ],
    [
        '#p' => [(new Bech32($_SERVER['RELAY_OWNER_NPUB']))()]
    ]
];

$follow_lists = $events([
    'kinds' => [3],
    'authors' => [$pubkey_owner]
        ]);
foreach ($follow_lists as $follow_list) {
    $whitelist_prototypes[0]['authors'] = array_reduce($follow_list->tags, function (array $authors, array $tag) {
        $authors[] = $tag[1];
        return $authors;
    }, $whitelist_prototypes[0]['authors']);
}

$store = new nostriphant\Transpher\Stores\Store($events, $whitelist_prototypes);

$relay = new \nostriphant\Transpher\Amp\Relay($store, $files_path);

$args = explode(":", $_SERVER['argv'][1]);
$args[] = $_SERVER['RELAY_MAX_CONNECTIONS_PER_IP'] ?? 1000;
$args[] = $logger;
$await = $relay(...$args);

$await(fn(int $signal) => $logger->info(sprintf("Received signal %d, stopping Relay server", $signal)));
