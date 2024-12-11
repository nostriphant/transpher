<?php

namespace nostriphant\TranspherTests;

use nostriphant\NIP01\Message;
use nostriphant\NIP01\Key;
use nostriphant\NIP59\Gift;
use nostriphant\NIP01\Event;
use nostriphant\NIP59\Seal;

class Client extends \nostriphant\Transpher\Client {

    private $expected_messages = [];

    public function expectNostrEvent(string $subscriptionId, string $content) {
        $this->expected_messages[] = ['EVENT', function (array $payload) use ($subscriptionId, $content) {
                expect($payload[0])->toBe($subscriptionId);
                expect($payload[1]['content'])->toBe($content);
            }];
    }
    public function expectNostrPrivateDirectMessage(string $subscriptionId, Key $recipient_key, string $message_content) {
        $this->expected_messages[] = ['EVENT', function (array $payload) use ($subscriptionId, $recipient_key, $message_content) {
                expect($payload[0])->toBe($subscriptionId);

                $gift = $payload[1];
                expect($gift['kind'])->toBe(1059);
            
            $seal = Gift::unwrap($recipient_key, Event::__set_state($gift));
                expect($seal->kind)->toBe(13);
                expect($seal->pubkey)->toBeString();
                expect($seal->content)->toBeString();

                $private_message = Seal::open($recipient_key, $seal);
                expect($private_message)->toHaveKey('id');
            expect($private_message)->toHaveKey('content');
            expect($private_message->content)->toBe($message_content);
            }];
        $this->expectNostrEose($subscriptionId);
    }
    public function expectNostrEose(string $subscriptionId) {
        $this->expected_messages[] = ['EOSE', function (array $payload) use ($subscriptionId) {
                expect($payload[0])->toBe($subscriptionId);
            }];
    }
    public function expectNostrNotice(string $expectedMessage) {
        $this->expected_messages[] = ['NOTICE', function (array $payload) use ($expectedMessage) {
                expect($payload[0])->toBe($expectedMessage);
            }];
    }
    public function expectNostrClosed(string $subscriptionId, string $expected_message) {
        $this->expected_messages[] = ['CLOSED', function (array $payload) use ($subscriptionId, $expected_message) {
                expect($payload[0])->toBe($subscriptionId);
                expect($payload[1])->toBe($expected_message);
            }];
    }
    
    public function sendSignedMessage(Message $signed_message) {
        $eventId = $signed_message()[1]['id'];
        $this->expected_messages[] = ['OK', function (array $payload) use ($eventId) {
                expect($payload[0])->toBe($eventId);
                expect($payload[1])->toBeTrue();
            }];
        $this->send($signed_message);
        $this->start();
    }
    
    public function __destruct() {
        if (count($this->expected_messages) > 0) {
            throw new \Exception('Lingering expected messages: ' . var_Export($this->expected_messages, true));
        }
    }

    #[\Override]
    public function start(int $timeout = 5): void {
        $this->onJson(function (callable $stop, Message $message) {
            $expected_message = array_shift($this->expected_messages);
            expect($message->type)->toBe($expected_message[0], 'Message type checks out');
            $expected_message[1]($message->payload);

            if (count($this->expected_messages) === 0) {
                $stop();
            }
        });
        
        parent::start($timeout);
    }
}
