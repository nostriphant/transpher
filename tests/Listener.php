<?php

namespace nostriphant\TranspherTests;

class Listener {

    readonly \Closure $logger;
    
    public function __construct(string $client, readonly private \nostriphant\NIP01\Key $recipient, public array $expected_messages = []) {
        $handle = fopen(dirname(__DIR__) . '/logs/' . $client . '.log', 'w');
        $this->logger = fn(string $message) => fwrite($handle, $message . PHP_EOL);

        ($this->logger)('>>> Starting log for client ' . $client . ' (' . ($this->recipient)(\nostriphant\NIP01\Key::public()) . ')');
    }
    
    static function expect(self $listener, array $message) : void {
        $listener->expected_messages[] = $message;
    }
    
    
    static function expectSubscription(self $listener, \nostriphant\Client\Subscription $subscription, string ...$events) {
        $expected_messages = array_map(fn($event_content) => ['EVENT', $subscription->id, $event_content], $events);
        $listener->expected_messages = array_merge($listener->expected_messages, $expected_messages);
        $listener->expected_messages[] = ['EOSE', $subscription->id];
        
        $subscription(function(?\nostriphant\NIP01\Event $event, callable $close, callable $stop) use ($listener, $subscription) {
            if (is_null($event)) {
                $listener(new \nostriphant\NIP01\Message('EOSE', $subscription->id), $stop);
            } else {
                $listener(new \nostriphant\NIP01\Message('EVENT', $subscription->id, $event()), $stop);
            }
        });
    }
    
    static function expectOK(self $listener, callable $send, \nostriphant\NIP01\Event $event) {
        $listener->expected_messages[] = ['OK', $event->id, true, ""];
        $send($event, function(bool $accepted, string $reason, callable $stop) use ($listener, $event) {
            $listener(new \nostriphant\NIP01\Message('OK', $event->id, $accepted, $reason), $stop);
        });
    }
    

    public function __invoke(\nostriphant\NIP01\Message $message, callable $stop) {
        $message_log = fn(string $log_message) => ($this->logger)('['.date('Y-m-d H:i:s').'] - ' . substr(sha1($message), 0, 6) . ' - ' . $log_message);

        $message_log('Received ' . $message);

        $remaining = [];
        foreach ($this->expected_messages as $expected_message) {
            $expected_type = $expected_message[0];
            $expected_payload = array_slice($expected_message, 1);

            if ($expected_type !== $message->type) {
                $message_log('Expected type ' . $expected_type . ' received ' . $message->type . ', skipping...');
                $remaining[] = $expected_message;
                continue;
            }

            switch ($message->type) {
                case 'EVENT':
                    if ($message->payload[0] !== $expected_payload[0]) {
                        $remaining[] = $expected_message;
                        $message_log('Expected subscription id ' . $expected_payload[0] . ' received ' . $message->payload[0] . ', skipping...');
                    } elseif ($message->payload[1]['kind'] === 1059) {
                        $gift = $message->payload[1];
                        expect($gift['tags'])->toContain(['p', ($this->recipient)(\nostriphant\NIP01\Key::public())]);

                        $seal = \nostriphant\NIP59\Gift::unwrap(($this->recipient), \nostriphant\NIP01\Event::__set_state($gift));
                        expect($seal->kind)->toBe(13);
                        expect($seal->pubkey)->toBeString();
                        expect($seal->content)->toBeString();

                        $private_message = \nostriphant\NIP59\Seal::open($this->recipient, $seal);
                        expect($private_message)->toHaveKey('id');
                        expect($private_message)->toHaveKey('content');
                        if ($private_message->content !== $expected_payload[1]) {
                            $message_log('Expected message "' . $expected_payload[1] . '", received "' . $private_message->content . '", skipping...');
                            $remaining[] = $expected_message;
                        }
                    } elseif ($message->payload[1]['content'] !== $expected_payload[1]) {
                        $message_log('Expected message "' . $expected_payload[1] . '", received "' . $message->payload[1]['content'] . '", skipping...');
                        $remaining[] = $expected_message;
                    } else {
                        $message_log('OK, removing expected message from stack...');
                    }
                    break;

                default:
                    if ($message->payload !== $expected_payload) {
                        $message_log('Expected payload ' . var_export($expected_payload, true) . ', skipping ...');
                        $remaining[] = $expected_message;
                    } else {
                        $message_log('OK, removing expected message from stack...');
                    }
                    break;
            }
        }

        $this->expected_messages = $remaining;
        if (count($this->expected_messages) === 0) {
            ($this->logger)('No more expected messages remaining, stop listening...');
            $stop();
        } else {
            ($this->logger)('Expected messages remaining ' . count($this->expected_messages));
            ($this->logger)(var_export($this->expected_messages, true));
        }
    }
}
