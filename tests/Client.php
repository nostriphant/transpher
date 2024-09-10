<?php

namespace TranspherTests;

use Transpher\Nostr;

/**
 * Description of Client
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Client extends \Transpher\WebSocket\Client {

    private $expected_messages = [];
    
    
    static function client(int $port) : self {
        return new self(new \WebSocket\Client("ws://127.0.0.1:" . $port));
    }
    static function generic_client() {
        return self::client(8081);
    }
    
    public function expectNostrOK(string $eventId) {
        $this->expected_messages[] = ['OK', function(\WebSocket\Client $client, array $message) use ($eventId) {
            expect($message[0])->toBe($eventId);
            expect($message[1])->toBeTrue();
            $client->stop();
        }];
    }
    public function expectNostrEvent(string $subscriptionId, string $content) {
        $this->expected_messages[] = ['EVENT', function(\WebSocket\Client $client, array $message) use ($subscriptionId, $content) {
            expect($message[0])->toBe($subscriptionId);
            expect($message[1]['content'])->toBe($content);
        }];
    }
    public function expectNostrPrivateDirectMessage(string $subscriptionId, string $content) {
        $this->expected_messages[] = ['EVENT', function(\WebSocket\Client $client, array $message) use ($subscriptionId, $content) {
            expect($message[0])->toBe($subscriptionId);
            expect($message[1]['kind'])->toBe(1059);
        }];
        $this->expectNostrEose($subscriptionId);
    }
    public function expectNostrEose(string $subscriptionId) {
        $this->expected_messages[] = ['EOSE', function(\WebSocket\Client $client, array $message) use ($subscriptionId) {
            expect($message[0])->toBe($subscriptionId);
            $client->stop();
        }];
    }
    public function expectNostrNotice(string $expectedMessage) {
        $this->expected_messages[] = ['NOTICE', function(\WebSocket\Client $client, array $message) use ($expectedMessage) {
            expect($message[0])->toBe($expectedMessage);
            $client->stop();
        }];
    }
    public function expectNostrClosed(string $subscriptionId, string $expected_message) {
        $this->expected_messages[] = ['CLOSED', function(\WebSocket\Client $client, array $message) use ($subscriptionId, $expected_message) {
            expect($message[0])->toBe($subscriptionId);
            expect($message[1])->toBe($expected_message);
            $client->stop();
        }];
    }
    
    public function sendSignedMessage(array $signed_message) {
        $this->expectNostrOK($signed_message[1]['id']);
        $this->json($signed_message);
        $this->start();
    }

    #[\Override]
    public function start(): void {
        $this->onJson(function(\WebSocket\Client $client, \WebSocket\Connection $connection, array $message) {
            $expected_message = array_shift($this->expected_messages);
            expect(array_shift($message))->toBe($expected_message[0]);
            $expected_message[1]($client, $message);
        });
        $wrapped_client = $this;
        parent::start();
    }
}
