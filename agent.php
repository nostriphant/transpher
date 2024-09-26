<?php

require_once __DIR__ . '/bootstrap.php';

use Transpher\Nostr\Message;
use \Transpher\Key;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

Transpher\Process::gracefulExit();

\Transpher\Nostr\Relay::boot($_SERVER['argv'][1] ?? 80, [], function(callable $relay) {
    $relay_url = $_SERVER['RELAY_URL'];
    
    $log = new Logger('agent');
    $log->pushHandler(new StreamHandler(__DIR__ . '/logs/agent.log', Level::Debug));
    $log->pushHandler(new StreamHandler(STDOUT), Level::Info);
    
    $agent_key = Key::fromBech32($_SERVER['AGENT_NSEC']);
    $log->info('Running agent with public key ' . $agent_key(Key::public(\Transpher\Nostr\Key\Format::BECH32)));
    
    $agent = new \Transpher\WebSocket\Client(new WebSocket\Client($relay_url), $log);
    $log->info('Sending Private Direct Message event');
    $note = Message::privateDirect($agent_key);
    $agent->json($note(Key::convertBech32ToHex($_SERVER['RELAY_OWNER_NPUB']), 'Hello, I am your agent! The URL of your relay is ' . $relay_url));
    $agent->start();
});
echo 'Done';