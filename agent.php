<?php
pcntl_async_signals(TRUE);

require_once __DIR__ . '/bootstrap.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;




\Transpher\Nostr\Server::boot($_SERVER['argv'][1], [], function(callable $relay) {
    \Functional\each(\Functional\filter(get_defined_constants(), fn(mixed $value, string $name) => str_starts_with($name, 'SIG')), function(mixed $value, string $name) use ($relay) {
        if (str_starts_with($name, 'SIG_')) {
        } elseif ($value === SIGKILL) {
        } elseif ($value === SIGSTOP) {
        } else {
            pcntl_signal(constant($name), $relay);
        }
    });

    $relay_url = 'ws://127.0.0.1:' . $_SERVER['argv'][1];
    
    $websocket = new WebSocket\Client($relay_url);
    $log = new Logger('agent');
    $log->pushHandler(new StreamHandler(__DIR__ . '/logs/agent.log', Level::Debug));
    $log->pushHandler(new StreamHandler(STDOUT), Level::Info);
    $websocket->setLogger($log);
    $agent = new \Transpher\WebSocket\Client($websocket);
    $agent->start();
});
echo 'Done';