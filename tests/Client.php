<?php

namespace TranspherTests;

use Transpher\Nostr;

/**
 * Description of Client
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Client extends \WebSocket\Client {

    private $expected_messages = [];
    
    public function expectNostrOK(string $eventId) {
        $this->expected_messages[] = ['OK', function(Client $client, array $message) use ($eventId) {
            expect($message[0])->toBe($eventId);
            expect($message[1])->toBeTrue();
            $client->stop();
        }];
    }
    public function expectNostrEvent(string $subscriptionId, string $content) {
        $this->expected_messages[] = ['EVENT', function(Client $client, array $message) use ($subscriptionId, $content) {
            expect($message[0])->toBe($subscriptionId);
            expect($message[1]['content'])->toBe($content);
        }];
    }
    public function expectNostrEose(string $subscriptionId) {
        $this->expected_messages[] = ['EOSE', function(Client $client, array $message) use ($subscriptionId) {
            expect($message[0])->toBe($subscriptionId);
            $client->stop();
        }];
    }
    public function expectNostrNotice(string $expectedMessage) {
        $this->expected_messages[] = ['NOTICE', function(Client $client, array $message) use ($expectedMessage) {
            expect($message[0])->toBe($expectedMessage);
            $client->stop();
        }];
    }
    public function expectNostrClosed(string $subscriptionId, string $expected_message) {
        $this->expected_messages[] = ['CLOSED', function(Client $client, array $message) use ($subscriptionId, $expected_message) {
            expect($message[0])->toBe($subscriptionId);
            expect($message[1])->toBe($expected_message);
            $client->stop();
        }];
    }
    
    public function json(mixed $json) {
        $this->text(Nostr::encode($json));
    }
    public function onJson(callable $callback) {
        Nostr::onJson($this, $callback);
    }
    
    #[\Override]
    public function start(): void {
        $this->onJson(function(\WebSocket\Client $client, \WebSocket\Connection $connection, array $message) {
            $expected_message = array_shift($this->expected_messages);
            expect(array_shift($message))->toBe($expected_message[0]);
            $expected_message[1]($client, $message);
        });
        
        parent::start();
    }
    
    public function close(int $status = 1000, string $message = 'ttfn'): \WebSocket\Message\Close {
        expect($this->expected_messages)->toHaveCount(0);
        return parent::close($status, $message);
    }
}
