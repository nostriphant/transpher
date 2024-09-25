<?php

require_once __DIR__ . '/bootstrap.php';

use Transpher\Message;
use \Transpher\Key;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

function handle_interrupt(int $sig, array $info){
    printf("Received INT signal, exiting gracefully\n");
    exit(0);
}
pcntl_signal(SIGTERM, 'handle_interrupt', false );
pcntl_async_signals(true);

$port = $_SERVER['argv'][1] ?? 80;

\Transpher\Nostr\Relay::boot($port, [], function(callable $relay) use ($port) {
    $relay_url = 'ws://127.0.0.1:' . $port;
    
    $log = new Logger('agent');
    $log->pushHandler(new StreamHandler(__DIR__ . '/logs/agent.log', Level::Debug));
    $log->pushHandler(new StreamHandler(STDOUT), Level::Info);
    
    $agent_key = Key::fromBech32($_SERVER['AGENT_NSEC']);
    $log->info('Running agent with public key ' . $agent_key(Key::public(\Transpher\Nostr\Key\Format::BECH32)));
    
    $agent = new \Transpher\WebSocket\Client(new WebSocket\Client($relay_url), $log);
    $log->info('Sending Private Direct Message event');
    $note = Message::privateDirect($agent_key);
    $agent->json($note(Key::convertBech32ToHex($_SERVER['AGENT_OWNER_NPUB']), 'Hello, I am Agent!'));
    $agent->start();
});
echo 'Done';