<?php

require dirname(__DIR__) . '/bootstrap.php';

print json_encode([
    "name" => $_ENV['RELAY_NAME'],
    "description" => $_ENV['RELAY_DESCRIPTION'],
    "pubkey" => \Transpher\Key::convertBech32ToHex($_ENV['RELAY_OWNER_NPUB']),
    "contact" => $_ENV['RELAY_CONTACT'],
    "supported_nips" => [1, 11],
    "software" => 'Transpher',
    "version" => 'dev'
]);