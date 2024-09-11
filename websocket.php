<?php

require_once __DIR__ . '/bootstrap.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

$port = $_SERVER['argv'][1] ?? $_ENV['TRANSPHER_PORT'] ?? 80;
$websocket = new WebSocket\Server($port);

// create a log channel
$log = new Logger('relay-' . $websocket->getPort());
$log->pushHandler(new StreamHandler(__DIR__ . '/logs/server.log', Level::Debug));
$log->pushHandler(new StreamHandler(STDOUT), Level::Info);

$server = new \Transpher\WebSocket\Server($websocket);
$websocket->setLogger($log);
        
if (isset($_SERVER['TRANSPHER_STORE']) === false) {
    $log->info('Using memory to save messages.');
    $events = [];
} elseif (str_starts_with($_SERVER['TRANSPHER_STORE'], 'redis')) {
    $log->info('Using redis to store messages');
    $events = new Transpher\Redis($_SERVER['TRANSPHER_STORE']);
} elseif (is_dir($_SERVER['TRANSPHER_STORE'])) {
    $log->info('Using directory to store messages');
    $events = new Transpher\Directory($_SERVER['TRANSPHER_STORE']);
} else {
    $log->info('Using memory to save messages (fallback).');
    $events = [];
}

$server->start($events, $log);