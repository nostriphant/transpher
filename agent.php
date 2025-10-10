<?php

use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use nostriphant\Transpher\Agent;

$log = (require_once __DIR__ . '/bootstrap.php')('agent', 'INFO', $_SERVER['AGENT_LOG_LEVEL'] ?? 'INFO');

$agent = new Agent(
        Key::fromHex((new Bech32($_SERVER['AGENT_NSEC']))()),
        new Bech32($_SERVER['RELAY_OWNER_NPUB'])
);

$log->info('Client connecting to ' . $_SERVER['RELAY_URL'], $_SERVER['RELAY_URL']);
$log->info('Listening to relay...');
$await = $agent();
$log->info('Running agent with public key ' . Bech32::npub(($_SERVER['AGENT_NSEC'])(Key::public())));
$log->info('Sending Private Direct Message event');

$await(fn(int $signal) => $log->info(sprintf("Received signal %d, stopping agent", $signal)));
