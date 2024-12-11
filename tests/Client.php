<?php

namespace nostriphant\TranspherTests;

use nostriphant\NIP01\Message;

class Client extends \nostriphant\Transpher\Client {

    private $expected_messages = [];

    public function expectNostr(string $type, callable $callback) {
        $this->expected_messages[] = [$type, $callback];
    }

    public function expectNostrEvent(callable $callback) {
        $this->expectNostr('EVENT', $callback);
    }

    public function expectNostrEose(string $subscriptionId) {
        $this->expectNostr('EOSE', function (array $payload) use ($subscriptionId) {
            expect($payload[0])->toBe($subscriptionId);
        });
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
