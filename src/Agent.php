<?php

namespace nostriphant\Transpher;
use nostriphant\Transpher\Client;
use Psr\Log\LoggerInterface;
use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use function \Amp\trapSignal;

readonly class Agent {

    public function __construct(#[\SensitiveParameter] private Key $key, private Bech32 $relay_owner_npub) {
        
    }
    
    public function __invoke(Client $client, LoggerInterface $log): callable {
        $log->info('Client connecting to ' . $client->url);

        $log->info('Running agent with public key ' . Bech32::npub(($this->key)(Key::public())));
        $log->info('Sending Private Direct Message event');
        $client->privateDirectMessage($this->key, call_user_func($this->relay_owner_npub), 'Hello, I am your agent! The URL of your relay is {relay_url}');

        $log->info('Listening to relay...');
        $client->start(0, function (callable $stop, \nostriphant\NIP01\Message $message) {

        });

        $signal = trapSignal([SIGINT, SIGTERM]);

        $log->info(sprintf("Received signal %d, stopping Relay server", $signal));

        $client->stop();
        $log->info('Done');
    }
}
