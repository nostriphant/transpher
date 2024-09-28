<?php

require_once __DIR__ . '/bootstrap.php';

use Transpher\Nostr\Message;
use \Transpher\Key;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

Transpher\Process::gracefulExit();
$log = new Logger('agent');
$log->pushHandler(new StreamHandler(__DIR__ . '/logs/agent.log', Level::Debug));
$log->pushHandler(new StreamHandler(STDOUT), Level::Info);

$relay_port = $_SERVER['argv'][1] ?? 80;

$hostname = 'localhost:'.($relay_port+1);
$cmd = [PHP_BINARY, '-d', 'variables_order=EGPCS', '-S', $hostname, '-t', ROOT_DIR . '/public'];
\Transpher\Process::start('relay-http', $cmd, [
    'RELAY_OWNER_NPUB' => $_SERVER['RELAY_OWNER_NPUB'], 
    'RELAY_NAME' => $_SERVER['RELAY_NAME'],
    'RELAY_DESCRIPTION' => $_SERVER['RELAY_DESCRIPTION'],
    'RELAY_CONTACT' => $_SERVER['RELAY_CONTACT']
], fn(string $line) => str_contains($line, 'Development Server (http://'.$hostname.') started'), function(Transpher\Process $http) use ($hostname, $relay_port, $log) {
    $log->info('HTTP server listening on http://'.$hostname.'...');
    
    \Transpher\Nostr\Relay::boot($relay_port, [], function(callable $relay) use ($log) {
        $relay_url = $_SERVER['RELAY_URL'];

        $agent_key = Key::fromBech32($_SERVER['AGENT_NSEC']);
        $log->info('Running agent with public key ' . $agent_key(Key::public(\Transpher\Nostr\Key\Format::BECH32)));

        $agent = new \Transpher\WebSocket\Client(new WebSocket\Client($relay_url), $log);
        $log->info('Sending Private Direct Message event');
        $note = Message::privateDirect($agent_key);
        $agent->json($note(Key::convertBech32ToHex($_SERVER['RELAY_OWNER_NPUB']), 'Hello, I am your agent! The URL of your relay is ' . $relay_url));
        $agent->start();
    });
    echo 'Done';
});
