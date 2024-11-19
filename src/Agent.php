<?php

namespace nostriphant\Transpher;
use nostriphant\Transpher\Client;
use Psr\Log\LoggerInterface;
use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;

/**
 * Description of Agent
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Agent {

    public function __construct(private Client $client, #[\SensitiveParameter] private Key $key, private string $relay_owner_npub) {
    }
    
    public function __invoke(LoggerInterface $log): callable {
        $log->info('Running agent with public key ' . Bech32::toNpub(($this->key)(Key::public())));
        $log->info('Sending Private Direct Message event');
        $this->client->privateDirectMessage($this->key, $this->relay_owner_npub, 'Hello, I am your agent! The URL of your relay is {relay_url}');
        
        $log->info('Listening to relay...');
        $this->client->start(0);

        return fn() => $this->client->stop();
    }
}
