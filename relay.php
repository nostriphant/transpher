<?php

$loglevel = $_SERVER['RELAY_LOG_LEVEL'] ?? 'INFO';
$logger = (require_once __DIR__ . '/bootstrap.php')('relay', $loglevel);
$logger->info('Log level ' . $_SERVER['RELAY_LOG_LEVEL'] ?? 'INFO');

use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Key;

$data_dir = $_SERVER['RELAY_DATA'];
is_dir($data_dir) || mkdir($data_dir);

$blossom = new nostriphant\Blossom\Blossom($data_dir . '/files');

$relay = new \nostriphant\Relay\Relay(new \nostriphant\Relay\InformationDocument(
    name: $_SERVER['RELAY_NAME'],
    description: $_SERVER['RELAY_DESCRIPTION'],
    pubkey: (new \nostriphant\NIP19\Bech32($_SERVER['RELAY_OWNER_NPUB']))(),
    contact: $_SERVER['RELAY_CONTACT'],
    supported_nips: [1, 2, 9, 11, 12, 13, 16, 20, 22, 33, 45],
    software: json_decode(file_get_contents(__DIR__ . '/composer.json'))->homepage,
    version: file_get_contents(__DIR__ . '/VERSION')
));

$server = $relay($_SERVER['argv'][1], $_SERVER['RELAY_MAX_CONNECTIONS_PER_IP'] ?? 1000, $logger, $blossom(new \nostriphant\Functional\FunctionList()));

$events = new nostriphant\Stores\Engine\SQLite(new SQLite3($data_dir . '/transpher.sqlite'));

$whitelist = [];
if (($_SERVER['RELAY_WHITELISTED_AUTHORS_ONLY'] ?? false)) {
    $agent_pubkey = Key::fromHex((new Bech32($_SERVER['AGENT_NSEC']))())(Key::public());
    $logger->debug('Whitelisting owner ('.$_SERVER['RELAY_OWNER_NPUB'].') and agent ('.$agent_pubkey.')');
    
    $whitelisted_npubs = array_filter(explode(',', $_SERVER['RELAY_WHITELISTED_AUTHORS'] ?? ''));
    $whitelisted_npubs[] = $_SERVER['RELAY_OWNER_NPUB'];
    
    $whitelisted_pubkeys = array_map(fn(string $npub) => (new Bech32($npub))(), $whitelisted_npubs);
    $whitelisted_pubkeys[] = $agent_pubkey;


    $logger->debug('Whitelisting followed npubs');
    $follow_lists = nostriphant\Stores\Store::query($events, ['kinds' => [3], 'authors' => $whitelisted_pubkeys]);
    foreach ($follow_lists as $follow_list) {
        $whitelisted_pubkeys = array_reduce($follow_list->tags, function (array $whitelisted_pubkeys, array $tag) use ($logger) {
            $whitelisted_pubkeys[] = $tag[1];
            $logger->debug('Found ' . $tag[1]);
            return $whitelisted_pubkeys;
        }, $whitelisted_pubkeys);
    }

    $whitelist[0] = ['authors' => $whitelisted_pubkeys];
    $whitelist[1] = ['#p' => $whitelisted_pubkeys];
}

$logger->info('Loading store ' . (!empty($whitelist) ? ' with whitelist' : '')  . '.');
$store = new nostriphant\Stores\Store($events, $whitelist);

$logger->debug('Starting relay.');
$stop = $server($store);

new nostriphant\Relay\AwaitSignal(function(int $signal) use ($stop, $logger) {
    $logger->info(sprintf("Received signal %d, stopping Relay server", $signal));
    $stop();
});