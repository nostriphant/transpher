<?php

require_once __DIR__ . '/bootstrap.php';

use nostriphant\Transpher\Client;
use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use function \Amp\trapSignal;

$log = new Logger('agent');
$log->pushHandler(new StreamHandler(__DIR__ . '/logs/agent.log', Level::Debug));
$log->pushHandler(new StreamHandler(STDOUT), Level::Info);

$relay_url = $_SERVER['RELAY_URL'];
$log->info('Client connecting to ' . $relay_url);
$agent = new nostriphant\Transpher\Agent(
    new Client($relay_url),
    Key::fromHex(Bech32::fromNsec($_SERVER['AGENT_NSEC'])),
    $_SERVER['RELAY_OWNER_NPUB']
);

$disconnect = $agent($log);

// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$log->info(sprintf("Received signal %d, stopping Relay server", $signal));

$disconnect();
$log->info('Done');
