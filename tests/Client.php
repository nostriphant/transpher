<?php

namespace rikmeijer\TranspherTests;

use rikmeijer\Transpher\Nostr;

/**
 * Description of Client
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Client extends \rikmeijer\Transpher\Client {

    private $expected_messages = [];
    
    
    static function client(int $port) : self {
        return new self("ws://127.0.0.1:" . $port);
    }
    static function generic_client() : self {
        return self::client(8081);
    }
    
    public function expectNostrOK(string $eventId) {
        $this->expected_messages[] = ['OK', function(callable $stop, array $message) use ($eventId) {
            expect($message[0])->toBe($eventId);
            expect($message[1])->toBeTrue();
        }];
    }
    public function expectNostrEvent(string $subscriptionId, string $content) {
        $this->expected_messages[] = ['EVENT', function(callable $stop, array $message) use ($subscriptionId, $content) {
            expect($message[0])->toBe($subscriptionId);
            expect($message[1]['content'])->toBe($content);
        }];
    }
    public function expectNostrPrivateDirectMessage(string $subscriptionId, callable $recipient_key, string $message_content) {
        $this->expectNostrEose($subscriptionId);
        $this->expected_messages[] = ['EVENT', function(callable $stop, array $message) use ($subscriptionId, $recipient_key, $message_content) {
            expect($message[0])->toBe($subscriptionId);
            
            $gift = $message[1];
            expect($gift['kind'])->toBe(1059);
            
            $seal = Nostr\Event\Gift::unwrap($recipient_key, $gift['pubkey'], $gift['content']);
            expect($seal['kind'])->toBe(13);
            expect($seal['pubkey'])->toBeString();
            expect($seal['content'])->toBeString();

            $private_message = Nostr\Event\Seal::open($recipient_key, $seal['pubkey'], $seal['content']);
            expect($private_message)->toBeArray();
            expect($private_message)->toHaveKey('id');
            expect($private_message)->toHaveKey('content');
            expect($private_message['content'])->toBe($message_content);
        }];
        $this->expectNostrEose($subscriptionId);
    }
    public function expectNostrEose(string $subscriptionId) {
        $this->expected_messages[] = ['EOSE', function(callable $stop, array $message) use ($subscriptionId) {
            expect($message[0])->toBe($subscriptionId);
        }];
    }
    public function expectNostrNotice(string $expectedMessage) {
        $this->expected_messages[] = ['NOTICE', function(callable $stop, array $message) use ($expectedMessage) {
            expect($message[0])->toBe($expectedMessage);
        }];
    }
    public function expectNostrClosed(string $subscriptionId, string $expected_message) {
        $this->expected_messages[] = ['CLOSED', function(callable $stop, array $message) use ($subscriptionId, $expected_message) {
            expect($message[0])->toBe($subscriptionId);
            expect($message[1])->toBe($expected_message);
        }];
    }
    
    public function sendSignedMessage(Nostr\Message $signed_message) {
        $this->expectNostrOK($signed_message()[1]['id']);
        $this->send($signed_message);
        $this->start();
    }
    
    public function __destruct() {
        expect($this->expected_messages)->toBeEmpty();
    }
    
    public function start(): void {
        $this->onJson(function(callable $stop, array $message) {
            $expected_message = array_shift($this->expected_messages);
            expect(array_shift($message))->toBe($expected_message[0], 'Message type checks out');
            $expected_message[1]($stop, $message);
            
            if (count($this->expected_messages) === 0) {
                $stop();
            }
        });
        
        parent::start();
    }
}
