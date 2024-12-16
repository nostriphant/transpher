<?php

$logger = (require_once __DIR__ . '/bootstrap.php')('relay', 'INFO', $_SERVER['RELAY_LOG_LEVEL'] ?? 'INFO');

use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Key;

$whitelist_prototypes = [
        [
        'authors' => [
                (new Bech32($_SERVER['RELAY_OWNER_NPUB']))(),
            Key::fromHex((new Bech32($_SERVER['AGENT_NSEC']))())(Key::public())
        ],
        ],
        [
            '#p' => [(new Bech32($_SERVER['RELAY_OWNER_NPUB']))()]
    ]
    ];
if (isset($_SERVER['RELAY_DATA'])) {
    $data_dir = $_SERVER['RELAY_DATA'];
    is_dir($data_dir) || mkdir($data_dir);

    $events = new nostriphant\Transpher\Stores\SQLite(new SQLite3($data_dir . '/transpher.sqlite'), $whitelist_prototypes);

    $store_path = $data_dir . '/events';
    if (is_dir($store_path)) {
        $logger->debug('Starting migrating events...');
        $logger->debug(\nostriphant\Transpher\Stores\Disk::walk_store($store_path, function (nostriphant\NIP01\Event $event) use ($store_path, &$events) {
                    $events[$event->id] = $event;
                    return unlink($store_path . '/' . $event->id . '.php');
                }) . ' events migrated.');
    }


    if (\nostriphant\Transpher\Nostr\Subscription::disabled($whitelist_prototypes) === false) {
        $housekeeper = \nostriphant\Transpher\Stores\generate_housekeeper($events);
        $housekeeper();
    }

    $files_path = $data_dir . '/files';
} else {
    $store_path = $_SERVER['RELAY_STORE'] ?? ROOT_DIR . '/data/events';
    $events = new \nostriphant\Transpher\Stores\Disk($store_path, $whitelist_prototypes);

    $files_path = $_SERVER['RELAY_FILES'] ?? ROOT_DIR . '/data/files';
}

$relay = new \nostriphant\Transpher\Amp\Relay($events, $files_path);

$args = explode(":", $_SERVER['argv'][1]);
$args[] = $_SERVER['RELAY_MAX_CONNECTIONS_PER_IP'] ?? 1000;
$args[] = $logger;
$await = $relay(...$args);

$await(fn(int $signal) => $logger->info(sprintf("Received signal %d, stopping Relay server", $signal)));
