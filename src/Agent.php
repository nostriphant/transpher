<?php

namespace rikmeijer\Transpher;
use rikmeijer\Transpher\Client;
use Psr\Log\LoggerInterface;
use rikmeijer\Transpher\Nostr\Key\Format;
use rikmeijer\Transpher\Nostr\Key;

/**
 * Description of Agent
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Agent {
    static function boot(int $port, array $env) : Process {
        $cmd = [PHP_BINARY, ROOT_DIR . DIRECTORY_SEPARATOR . 'agent.php', $port];
        return new Process('agent-' . $port, $cmd, $env, fn(string $line) => str_contains($line, 'Client connecting to ws://127.0.0.1'));
    }
    
    public function __construct(private Client $client, #[\SensitiveParameter] private Key $key, private string $relay_owner_npub) {
    }
    
    public function __invoke(LoggerInterface $log): callable {
        $log->info('Running agent with public key ' . (($this->key)(Key::public(Format::BECH32))));
        $log->info('Sending Private Direct Message event');
        $this->client->privateDirectMessage($this->key, $this->relay_owner_npub, 'Hello, I am your agent! The URL of your relay is {relay_url}');
        
        $log->info('Listening to relay...');
        $this->client->start();
        
        return fn() => $this->client->stop();
    }
}
