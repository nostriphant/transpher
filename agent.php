<?php
pcntl_async_signals(TRUE);

require_once __DIR__ . '/bootstrap.php';

use Transpher\Message;
use \Transpher\Key;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;


$port = $_SERVER['argv'][1] ?? 80;

\Transpher\Nostr\Relay::boot($port, [], function(callable $relay) use ($port) {
    \Functional\each(\Functional\filter(get_defined_constants(), fn(mixed $value, string $name) => str_starts_with($name, 'SIG')), function(mixed $value, string $name) use ($relay) {
        if (str_starts_with($name, 'SIG_')) {
        } elseif ($value === SIGKILL) {
        } elseif ($value === SIGSTOP) {
        } else {
            pcntl_signal(constant($name), $relay);
        }
    });

    $relay_url = 'ws://127.0.0.1:' . $port;
    
    $log = new Logger('agent');
    $log->pushHandler(new StreamHandler(__DIR__ . '/logs/agent.log', Level::Debug));
    $log->pushHandler(new StreamHandler(STDOUT), Level::Info);
    $agent = new \Transpher\WebSocket\Client(new WebSocket\Client($relay_url), $log);
    $log->info('Sending Private Direct Message event');
    $note = Message::privateDirect(Key::fromBech32($_SERVER['AGENT_NSEC']));
    $agent->json($note(Key::convertBech32ToHex($_SERVER['AGENT_OWNER_NPUB']), 'Hello, I am Agent!'));
    $agent->start();
});
echo 'Done';