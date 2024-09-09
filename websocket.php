<?php

require_once __DIR__ . '/bootstrap.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// create a log channel
$log = new Logger('file');
$log->pushHandler(new StreamHandler(__DIR__ . '/logs/server.log', Level::Info));

$port = $_SERVER['argv'][1] ?? $_ENV['TRANSPHER_PORT'] ?? 80;
$server = new \Transpher\WebSocket\Server(new WebSocket\Server($port));

if (isset($_SERVER['TRANSPHER_STORE']) === false) {
    $log->info('Using memory to save messages.');
    $events = [];
} elseif (str_starts_with($_SERVER['TRANSPHER_STORE'], 'redis')) {
    $log->info('Using redis to store messages');
    $events = new Transpher\Redis($_SERVER['TRANSPHER_STORE']);
}

echo PHP_EOL . 'Listening on ' . $port . '...';
$server->start($events, $log);