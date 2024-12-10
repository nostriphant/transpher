<?php

require_once __DIR__ . '/bootstrap.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Key;

$logger = new Logger('relay');
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/server.log', match (strtoupper($_SERVER['RELAY_LOG_LEVEL'] ?? 'INFO')) {
                    'DEBUG' => Level::Debug,
                    'NOTICE' => Level::Notice,
                    'INFO' => Level::Info,
                    'WARNING' => Level::Warning,
                    'ERROR' => Level::Error,
                    'CRITICAL' => Level::Critical,
                    'ALERT' => Level::Alert,
                    'EMERGENCY' => Level::Emergency,
                    default => Level::Info
                }));
$logger->pushHandler(new StreamHandler(STDOUT, Level::Info));

Monolog\ErrorHandler::register($logger);

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

    $files_path = $data_dir . '/files';
} else {
    $store_path = $_SERVER['RELAY_STORE'] ?? ROOT_DIR . '/data/events';
    $events = new \nostriphant\Transpher\Stores\Disk($store_path, $whitelist_prototypes);

    $files_path = $_SERVER['RELAY_FILES'] ?? ROOT_DIR . '/data/files';
}

$relay = new \nostriphant\Transpher\Relay($events, $files_path, $logger);

$args = explode(":", $_SERVER['argv'][1]);
$args[] = $_SERVER['RELAY_MAX_CONNECTIONS_PER_IP'] ?? 1000;
$relay(...$args);
