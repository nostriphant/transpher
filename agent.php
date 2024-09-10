<?php

require_once __DIR__ . '/bootstrap.php';

\Transpher\Nostr\Server::boot($_SERVER['argv'][1], [], function(callable $relay) {
    $relay_url = 'ws://127.0.0.1:' . $_SERVER['argv'][1];
    $agent = new \Transpher\WebSocket\Client(new WebSocket\Client($relay_url));

    echo PHP_EOL . "Listening to server @ " . $relay_url .  "...";
    echo PHP_EOL;
    $agent->start();
});
echo 'Done';