<?php

namespace nostriphant\Transpher;

use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use nostriphant\Client\Client;

readonly class Agent {

    private Client $client;
    private Key $nsec;
    private string $relay_owner_hex;

    private Agent\PrivateDirectMessageFactory $private_transmitter_factory;

    public function __construct(private \Psr\Log\LoggerInterface $logger, private string $relay_url, string $agent_nsec, private string $relay_owner_npub) {
        $this->client = new Client($relay_url);
        $this->nsec = Key::fromHex((new Bech32($agent_nsec))());
        $this->relay_owner_hex = (new Bech32($this->relay_owner_npub))();
        $this->private_transmitter_factory = new Agent\PrivateDirectMessageFactory($this->nsec, $this->relay_owner_hex, $this->logger);
    }

    public function __invoke() : void {
        ($this->client)(function(callable $send, callable $subscribe) {
            $private_transmitter = ($this->private_transmitter_factory)($send);
            $private_transmitter('Hello, I am your agent! The URL of your relay is ' . $this->relay_url);

            $pubkey = Key::derivePublicKey($this->nsec);
            $private_transmitter('Running with public key ' . Bech32::npub($pubkey));
        }, [$this->logger, 'debug']);
    }
}
