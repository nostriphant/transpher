<?php

$log = (require_once __DIR__ . '/bootstrap.php')('agent', 'INFO', 'DEBUG');

use nostriphant\Transpher\Client;

$agent = new nostriphant\Transpher\Agent(
        $_SERVER['AGENT_NSEC'],
        $_SERVER['RELAY_OWNER_NPUB']
);

$stop = $agent(new Client(0, $_SERVER['RELAY_URL']), $log);

$signal = Amp\trapSignal([SIGINT, SIGTERM]);
$log->info(sprintf("Received signal %d, stopping agent", $signal));

$stop();
