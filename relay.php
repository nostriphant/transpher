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
        $migrated = \nostriphant\Stores\Engine\Disk::walk_store($store_path, function (nostriphant\NIP01\Event $event) use ($store_path, &$events, $logger) {
                    $event_id = $event->id;
                    $events[$event_id] = $event;
                    $logger->debug('Event ' . $event_id . ' migrated, removing old file ' . $store_path . '/' . $event_id . '.php');
                    return unlink($store_path . '/' . $event_id . '.php');
                });
        $logger->debug($migrated . ' events migrated.');
    }

    $files_path = $data_dir . '/files';
} else {
    $store_path = $_SERVER['RELAY_STORE'] ?? ROOT_DIR . '/data/events';
    $events = new \nostriphant\Stores\Engine\Disk($store_path);

    $files_path = $_SERVER['RELAY_FILES'] ?? ROOT_DIR . '/data/files';
}

$whitelist = [];
if (($_SERVER['RELAY_WHITELISTED_AUTHORS_ONLY'] ?? false)) {
    $agent_pubkey = Key::fromHex((new Bech32($_SERVER['AGENT_NSEC']))())(Key::public());
    $logger->debug('Whitelisting owner ('.$_SERVER['RELAY_OWNER_NPUB'].') and agent ('.$agent_pubkey.')');
    $whitelisted_pubkeys = array_merge([
        (new Bech32($_SERVER['RELAY_OWNER_NPUB']))(),
        $agent_pubkey
    ], array_map(fn(string $npub) => (new Bech32($npub))(), explode(',', $_SERVER['RELAY_WHITELISTED_AUTHORS'] ?? '')));


    $logger->debug('Whitelisting followed npubs');
    $follow_lists = nostriphant\Stores\Store::query($events, ['kinds' => [3], 'authors' => $whitelisted_pubkeys]);
    foreach ($follow_lists as $follow_list) {
        $whitelisted_pubkeys = array_reduce($follow_list->tags, function (array $whitelisted_pubkeys, array $tag) use ($logger) {
            $whitelisted_pubkeys[] = $tag[1];
            $logger->debug('Found ' . $tag[1]);
            return $whitelisted_pubkeys;
        }, $whitelisted_pubkeys);
    }

    $whitelist[0] = ['authors' => $whitelisted_pubkeys];
    $whitelist[1] = ['#p' => $whitelisted_pubkeys];
}

$logger->info('Loading store ' . (!empty($whitelist) ? ' with withlist' : '')  . '.');
$store = new nostriphant\Stores\Store($events, $whitelist);

$relay = new \nostriphant\Relay\Relay($store, $files_path,
        $_SERVER['RELAY_NAME'],
        $_SERVER['RELAY_DESCRIPTION'],
        $_SERVER['RELAY_OWNER_NPUB'],
        $_SERVER['RELAY_CONTACT']
        );

$logger->debug('Starting relay.');
$stop = $relay($_SERVER['argv'][1], $_SERVER['RELAY_MAX_CONNECTIONS_PER_IP'] ?? 1000, $logger);

new nostriphant\Relay\AwaitSignal(function(int $signal) use ($stop, $logger) {
    $logger->info(sprintf("Received signal %d, stopping Relay server", $signal));
    $stop();
});