<?php

require_once __DIR__ . '/bootstrap.php';

use Transpher\Nostr\Message;
use \Transpher\Key;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Transpher\Process;

$log = new Logger('agent');
$log->pushHandler(new StreamHandler(__DIR__ . '/logs/agent.log', Level::Debug));
$log->pushHandler(new StreamHandler(STDOUT), Level::Info);

$hostname = '0.0.0.0:'. ($_SERVER['argv'][1] ?? 80);
$http = new Process('http', [PHP_BINARY, ROOT_DIR . '/http.php', $hostname], [
    'RELAY_OWNER_NPUB' => $_SERVER['RELAY_OWNER_NPUB'] ?? null, 
    'RELAY_NAME' => $_SERVER['RELAY_NAME'] ?? 'Transpher',
    'RELAY_DESCRIPTION' => $_SERVER['RELAY_DESCRIPTION'] ?? 'Nostr Relay written in PHP',
    'RELAY_CONTACT' => $_SERVER['RELAY_CONTACT'] ?? 'Nobody'
], fn(string $line) => str_contains($line, 'Listening on http://'.$hostname.'/'));

$log->info('HTTP server listening on http://'.$hostname.'...');

$relay_url = $_SERVER['RELAY_URL'];

$agent_key = Key::fromBech32($_SERVER['AGENT_NSEC']);
$log->info('Running agent with public key ' . $agent_key(Key::public(\Transpher\Nostr\Key\Format::BECH32)));

$log->info('Client connecting to ' . $relay_url);
$agent = new \Transpher\WebSocket\Client($relay_url);
$log->info('Sending Private Direct Message event');
$note = Message::privateDirect($agent_key);
$agent->json($note(Key::convertBech32ToHex($_SERVER['RELAY_OWNER_NPUB']), 'Hello, I am your agent! The URL of your relay is ' . $relay_url));
$log->info('Listening to relay...');
$agent->start();

// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$log->info(sprintf("Received signal %d, stopping Relay server", $signal));

$agent->stop();
$http();
$log->info('Done');
