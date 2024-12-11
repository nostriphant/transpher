<?php

$log = (require_once __DIR__ . '/bootstrap.php')('agent', 'INFO', $_SERVER['AGENT_LOG_LEVEL'] ?? 'INFO');

use nostriphant\Transpher\Client;

$agent = new nostriphant\Transpher\Agent(
        $_SERVER['AGENT_NSEC'],
        $_SERVER['RELAY_OWNER_NPUB']
);

$await = $agent(new Client(0, $_SERVER['RELAY_URL']), $log);

$await(fn(int $signal) => $log->info(sprintf("Received signal %d, stopping agent", $signal)));
