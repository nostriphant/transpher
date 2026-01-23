<?php

$loglevel = $_SERVER['RELAY_LOG_LEVEL'] ?? 'INFO';
$logger = (require_once __DIR__ . '/bootstrap.php')('relay', $loglevel);
$logger->info('Log level ' . $_SERVER['RELAY_LOG_LEVEL'] ?? 'INFO');

use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Key;

$data_dir = $_SERVER['RELAY_DATA'];
is_dir($data_dir) || mkdir($data_dir);

$events = new nostriphant\Stores\Engine\SQLite(new SQLite3($data_dir . '/transpher.sqlite'));
$files_path = $data_dir . '/files';

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
$blossom = new nostriphant\Relay\Blossom($files_path);
$server = new nostriphant\Relay\Amp\WebsocketServer(new \nostriphant\Relay\MessageHandlerFactory($store, $logger), $logger, fn(callable $define) => $blossom($define));

$relay = new \nostriphant\Relay\Relay($server,
        $_SERVER['RELAY_NAME'],
        $_SERVER['RELAY_DESCRIPTION'],
        $_SERVER['RELAY_OWNER_NPUB'],
        $_SERVER['RELAY_CONTACT']
        );

$logger->debug('Starting relay.');
$stop = $relay($_SERVER['argv'][1], $_SERVER['RELAY_MAX_CONNECTIONS_PER_IP'] ?? 1000);

new nostriphant\Relay\AwaitSignal(function(int $signal) use ($stop, $logger) {
    $logger->info(sprintf("Received signal %d, stopping Relay server", $signal));
    $stop();
});