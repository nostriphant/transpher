<?php

$loglevel = $_SERVER['AGENT_LOG_LEVEL'] ?? 'INFO';
$logger = (require_once __DIR__ . '/bootstrap.php')('agent', $loglevel);
$logger->info('Log level ' . $_SERVER['AGENT_LOG_LEVEL'] ?? 'INFO');

$logger->info('Client connecting to ' . $_SERVER['RELAY_URL']);
$agent = new nostriphant\Transpher\Agent($_SERVER['RELAY_URL'], $_SERVER['AGENT_NSEC'], $_SERVER['RELAY_OWNER_NPUB']);

$logger->info('Listening to relay...');
$agent($logger);
