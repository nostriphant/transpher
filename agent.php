<?php
pcntl_async_signals(TRUE);

require_once __DIR__ . '/bootstrap.php';

use Transpher\Message;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;




\Transpher\Nostr\Relay::boot($_SERVER['argv'][1], [], function(callable $relay) {
    \Functional\each(\Functional\filter(get_defined_constants(), fn(mixed $value, string $name) => str_starts_with($name, 'SIG')), function(mixed $value, string $name) use ($relay) {
        if (str_starts_with($name, 'SIG_')) {
        } elseif ($value === SIGKILL) {
        } elseif ($value === SIGSTOP) {
        } else {
            pcntl_signal(constant($name), $relay);
        }
    });

    $relay_url = 'ws://127.0.0.1:' . $_SERVER['argv'][1];
    
    $log = new Logger('agent');
    $log->pushHandler(new StreamHandler(__DIR__ . '/logs/agent.log', Level::Debug));
    $log->pushHandler(new StreamHandler(STDOUT), Level::Info);
    $agent = new \Transpher\WebSocket\Client(new WebSocket\Client($relay_url), $log);
    $log->info('Sending Private Direct Message event');
    $note = Message::privateDirect(\Transpher\Key::fromHex($_SERVER['AGENT_KEY']));
    $agent->json($note($_SERVER['AGENT_OWNER_PUBKEY'], 'Hello, I am Agent!'));
    $agent->start();
});
echo 'Done';