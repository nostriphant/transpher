<?php

require_once __DIR__ . '/bootstrap.php';

use Transpher\Nostr\Message;
use \Transpher\Key;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use function \Amp\trapSignal;

$relay_url = $_SERVER['RELAY_URL'];
$agent_nsec = $_SERVER['AGENT_NSEC'];
$relay_owner_npub = $_SERVER['RELAY_OWNER_NPUB'];

$log = new Logger('agent');
$log->pushHandler(new StreamHandler(__DIR__ . '/logs/agent.log', Level::Debug));
$log->pushHandler(new StreamHandler(STDOUT), Level::Info);


$agent_key = Key::fromBech32($agent_nsec);
$log->info('Running agent with public key ' . $agent_key(Key::public(\Transpher\Nostr\Key\Format::BECH32)));

$log->info('Client connecting to ' . $relay_url);
$agent = new \Transpher\WebSocket\Client($relay_url);
$log->info('Sending Private Direct Message event');
$note = Message::privateDirect($agent_key);
$agent->json($note(Key::convertBech32ToHex($relay_owner_npub), 'Hello, I am your agent! The URL of your relay is ' . $relay_url));
$log->info('Listening to relay...');
$agent->start();

// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$log->info(sprintf("Received signal %d, stopping Relay server", $signal));

$agent->stop();
$log->info('Done');
