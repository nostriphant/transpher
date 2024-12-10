<?php

$log = (require_once __DIR__ . '/bootstrap.php')('agent', 'INFO', 'DEBUG');

use nostriphant\Transpher\Client;
use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use function \Amp\trapSignal;

$log->info('Client connecting to ' . $_SERVER['RELAY_URL']);
$agent = new nostriphant\Transpher\Agent(
    new Client($_SERVER['RELAY_URL']),
        Key::fromHex((new Bech32($_SERVER['AGENT_NSEC']))()),
    new Bech32($_SERVER['RELAY_OWNER_NPUB'])
);

$disconnect = $agent($log);

// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$log->info(sprintf("Received signal %d, stopping Relay server", $signal));

$disconnect();
$log->info('Done');
