<?php

namespace nostriphant\Transpher\Amp;

use Psr\Log\LoggerInterface;
use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use nostriphant\Transpher\Nostr\Message\Factory;

readonly class Agent {
    
    private Key $key;
    private Bech32 $relay_owner_npub;

    public function __construct(#[\SensitiveParameter] string $agent_nsec, string $relay_owner_npub) {
        $this->key = Key::fromHex((new Bech32($agent_nsec))());
        $this->relay_owner_npub = new Bech32($relay_owner_npub);
    }
    
    public function __invoke(Client $client, LoggerInterface $log): AwaitSignal {
        $log->info('Client connecting to ' . $client->url);
        $log->info('Listening to relay...');
        $send = $client->start(function (\nostriphant\NIP01\Message $message) {
            
        });

        $log->info('Running agent with public key ' . Bech32::npub(($this->key)(Key::public())));
        $log->info('Sending Private Direct Message event');
        $send(\nostriphant\Transpher\Nostr\Message\PrivateDirect::make($this->key, call_user_func($this->relay_owner_npub), 'Hello, I am your agent! The URL of your relay is ' . $client->url));

        return new AwaitSignal(fn() => $client->stop());
    }
}
