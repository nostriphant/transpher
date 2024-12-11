<?php

namespace nostriphant\Transpher;
use nostriphant\Transpher\Client;
use Psr\Log\LoggerInterface;
use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use function \Amp\trapSignal;
use nostriphant\Transpher\Nostr\Message\Factory;

readonly class Agent {

    public function __construct(#[\SensitiveParameter] private Key $key, private Bech32 $relay_owner_npub) {
        
    }
    
    public function __invoke(Client $client, LoggerInterface $log): void {
        $log->info('Client connecting to ' . $client->url);

        $log->info('Running agent with public key ' . Bech32::npub(($this->key)(Key::public())));
        $log->info('Sending Private Direct Message event');
        $client->send(Factory::privateDirect($this->key, call_user_func($this->relay_owner_npub), 'Hello, I am your agent! The URL of your relay is ' . $client->url));

        $log->info('Listening to relay...');
        $client->start(function (callable $stop, \nostriphant\NIP01\Message $message) {
            
        });

        $signal = trapSignal([SIGINT, SIGTERM]);

        $log->info(sprintf("Received signal %d, stopping Relay server", $signal));

        $client->stop();
        $log->info('Done');
    }
}
