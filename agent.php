<?php

$log = (require_once __DIR__ . '/bootstrap.php')('agent', 'INFO', $_SERVER['AGENT_LOG_LEVEL'] ?? 'INFO');

$log->info('Client connecting to ' . $_SERVER['RELAY_URL']);
$agent = new \nostriphant\Transpher\Agent($_SERVER['RELAY_URL'], $_SERVER['AGENT_NSEC'], $_SERVER['RELAY_OWNER_NPUB']);

$log->info('Listening to relay...');
$agent();
