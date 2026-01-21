<?php

namespace nostriphant\Transpher;

use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use nostriphant\Client\Client;
use nostriphant\NIP17\PrivateDirect;
use nostriphant\NIP01\Message;
use nostriphant\NIP01\Transmission;

readonly class Agent {
    
    private Client $client;
    private Key $nsec;
    private string $relay_owner_hex;
    
    public function __construct(private string $relay_url, string $agent_nsec, private string $relay_owner_npub) {
        $this->client = Client::connectToUrl($relay_url);
        $this->nsec = Key::fromHex((new Bech32($agent_nsec))());
        $this->relay_owner_hex = (new Bech32($this->relay_owner_npub))(); 
    }
    
    private static function createPrivateDirectMessageFactory(Key $nsec, string $recipient_pubkey, \Psr\Log\LoggerInterface $logger) : callable {
        $logger->debug('Create transmitter to ' . $recipient_pubkey);
        $gift = fn(string $message) => Message::event(PrivateDirect::make($nsec, $recipient_pubkey, $message));
        return function(Transmission $send) use ($gift, $logger) { 
            return function(string $message) use ($gift, $send, $logger) { 
                $logger->debug('Sending encrypted message "' . $message . '".');
                return $send($gift($message));
            };
        };
    }
    
    public function __invoke(\Psr\Log\LoggerInterface $logger) : void {
        $private_transmitter_factory = self::createPrivateDirectMessageFactory($this->nsec, $this->relay_owner_hex, $logger);
        
        $listen = ($this->client)(function(Transmission $send) use ($private_transmitter_factory) {
            $private_transmitter = $private_transmitter_factory($send);
            $private_transmitter('Hello, I am your agent! The URL of your relay is ' . $this->relay_url);
            
            $pubkey = ($this->nsec)(Key::public());
            $private_transmitter('Running with public key ' . Bech32::npub($pubkey));
        });
        $listen(function (Message $message, callable $stop) {
        });
    }
}
