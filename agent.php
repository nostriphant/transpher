<?php

use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use nostriphant\Transpher\Agent;
use nostriphant\NIP17\PrivateDirect;
use nostriphant\NIP01\Message;

$log = (require_once __DIR__ . '/bootstrap.php')('agent', 'INFO', $_SERVER['AGENT_LOG_LEVEL'] ?? 'INFO');


$log->info('Client connecting to ' . $_SERVER['RELAY_URL']);
$agent = new Agent($_SERVER['RELAY_URL']);

$await = $agent(function(callable $send) use ($log) {
    $key = Key::fromHex((new Bech32($_SERVER['AGENT_NSEC']))());
    $relay_owner_npub = new Bech32($_SERVER['RELAY_OWNER_NPUB']);
    
    $log->info('Running agent with public key ' . $key(Key::public()));
    $log->info('Sending Private Direct Message event');
    $gift = PrivateDirect::make($key, $relay_owner_npub(), 'Hello, I am your agent! The URL of your relay is ' . $_SERVER['RELAY_URL']);
    $send(Message::event($gift));
});

$log->info('Listening to relay...');
$await(fn(int $signal) => $log->info(sprintf("Received signal %d, stopping agent", $signal)));
