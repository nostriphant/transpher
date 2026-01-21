<?php

use nostriphant\NIP01Tests\Functions as NIP01TestFunctions;
use nostriphant\NIP19\Bech32;
use nostriphant\TranspherTests\AcceptanceCase;
use nostriphant\TranspherTests\Factory;

use nostriphant\Client\Client;
use nostriphant\NIP01\Message;

describe('only events from whitelisted authors/recipients are stored', function() {
    
    function start_relay(string $data_dir, array $whitelisted_npubs) {
        return AcceptanceCase::bootRelay(AcceptanceCase::relay_url('tcp://'), [
            'AGENT_NSEC' => (string) 'nsec1ffqhqzhulzesndu4npay9rn85kvwyfn8qaww9vsz689pyf5sfz7smpc6mn',
            'RELAY_URL' => AcceptanceCase::relay_url(),
            'RELAY_OWNER_NPUB' => (string) Bech32::npub(NIP01TestFunctions::pubkey_recipient()),
            'RELAY_NAME' => 'Really relay',
            'RELAY_DESCRIPTION' => 'This is my dev relay',
            'RELAY_CONTACT' => 'transpher@nostriphant.dev',
            'RELAY_DATA' => $data_dir,
            'RELAY_LOG_LEVEL' => 'DEBUG',
            'RELAY_WHITELISTED_AUTHORS_ONLY' => 1,
            'RELAY_WHITELISTED_AUTHORS' => implode(',', $whitelisted_npubs),
            'LIMIT_EVENT_CREATED_AT_LOWER_DELTA' => 60 * 60 * 72, // to accept NIP17 pdm created_at randomness
        ]);
    }
    function start_agent() {
        return AcceptanceCase::bootAgent(8087, [
            'RELAY_OWNER_NPUB' => (string) Bech32::npub(NIP01TestFunctions::pubkey_recipient()),
            'AGENT_NSEC' => (string) 'nsec1ffqhqzhulzesndu4npay9rn85kvwyfn8qaww9vsz689pyf5sfz7smpc6mn',
            'RELAY_URL' => AcceptanceCase::relay_url(),
            'AGENT_LOG_LEVEL' => 'DEBUG',
        ]);
    }
    

    it('only stores messages from owner and agent, but they are still being delivered', function () {
        $data_dir = AcceptanceCase::data_dir('8087');
        
        $relay = start_relay($data_dir, []);
        $agent = start_agent();

        try {
            $alices_expected_messages = [];
            $alice = Client::connectToUrl(AcceptanceCase::relay_url());
            $bob = Client::connectToUrl(AcceptanceCase::relay_url());

            $unwrapper = AcceptanceCase::unwrap(NIP01TestFunctions::key_recipient());
            $subscriptionAlice = Factory::subscribe(['#p' => [NIP01TestFunctions::pubkey_recipient()]]);

            $alice_listen = $alice(function(callable $send) use (&$alices_expected_messages, $subscriptionAlice) {

                $subscriptionId = $subscriptionAlice()[1];
                $send($subscriptionAlice);

                $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Hello, I am your agent! The URL of your relay is ' . AcceptanceCase::relay_url()];
                $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Running with public key npub1'];
                $alices_expected_messages[] = ['EOSE', $subscriptionId];

                $request = $subscriptionAlice();
                expect($request[2])->toBeArray();
                expect($request[2]['#p'])->toContain(NIP01TestFunctions::pubkey_recipient());

                $signed_message = Factory::event(NIP01TestFunctions::key_recipient(), 1, 'Hello!');
                $send($signed_message);
                $alices_expected_messages[] = ['OK', $signed_message()[1]['id'], true];
            });


            $bob_message = Factory::event(NIP01TestFunctions::key_sender(), 1, 'Hello!');

            $bobs_expected_messages = [];
            $bob_listen = $bob(function(callable $send) use ($bob_message, &$bobs_expected_messages, &$alices_expected_messages, $subscriptionAlice) {
                $send($bob_message);
                $bobs_expected_messages[] = ['OK', $bob_message()[1]['id'], true, ''];
                $alices_expected_messages[] = ['EVENT', $subscriptionAlice()[1], 'Hello!'];
            });

            expect($bobs_expected_messages)->toHaveCount(1);

            $bob_listen(function (Message $message, callable $stop) use (&$bobs_expected_messages) {
                $expected_message = array_shift($bobs_expected_messages);

                $type = array_shift($expected_message);
                expect($message->type)->toBe($type, 'Message type checks out');
                expect($message->payload)->toBe($expected_message);

                if (count($bobs_expected_messages) === 0) {
                    $stop();
                }
            });

            $alice_listen(function (Message $message, callable $stop) use ($unwrapper, &$alices_expected_messages, $data_dir) {
                $remaining = [];
                foreach ($alices_expected_messages as $expected_message) {
                    if ($expected_message[0] !== $message->type) {
                        $remaining[] = $expected_message;
                        continue;
                    }
                    switch ($message->type) {
                        case 'EVENT':
                            if ($message->payload[0] !== $expected_message[1]) {
                                $remaining[] = $expected_message;
                            } elseif ($unwrapper($message->payload[1]) !== $expected_message[2]) {
                                $remaining[] = $expected_message;
                            }
                            break;

                        default:
                            if ($message->payload !== $expected_message) {
                                $remaining[] = $expected_message;
                            }
                            break;

                    }

                }
                $alices_expected_messages = $remaining;
                if (count($alices_expected_messages) === 0) {
                    $stop();
                }
            });


            $events = new nostriphant\Stores\Engine\SQLite(new SQLite3($data_dir . '/transpher.sqlite'), []);

            $notes_alice = iterator_to_array(nostriphant\Stores\Store::query($events, ['authors' => [NIP01TestFunctions::pubkey_recipient()], 'kinds' => [1]]));
            expect($notes_alice[0]->kind)->toBe(1);
            expect($notes_alice[0]->content)->toBe('Hello!');

            $notes_bob = iterator_to_array(nostriphant\Stores\Store::query($events, ['ids' => [$bob_message()[1]['id']]]));


            expect($notes_bob)->toHaveLength(0);
        } catch (\Exception $e) {
            $agent();
            $relay();
            throw $e;
        }

        $agent();
        $relay();
    });
    
    
    it('stores messages from owner, agent and whitelisted', function () {
        $data_dir = AcceptanceCase::data_dir('8087');
        
        $relay = start_relay($data_dir, [(string) Bech32::npub(NIP01TestFunctions::pubkey_sender())]);
        $agent = start_agent();

        try {
            $alices_expected_messages = [];
            $alice = Client::connectToUrl(AcceptanceCase::relay_url());
            $bob = Client::connectToUrl(AcceptanceCase::relay_url());

            $unwrapper = AcceptanceCase::unwrap(NIP01TestFunctions::key_recipient());

            $alice_listen = $alice(function(callable $send) use (&$alices_expected_messages) {
                $subscription = Factory::subscribe(['#p' => [NIP01TestFunctions::pubkey_recipient()]]);

                $subscriptionId = $subscription()[1];
                $send($subscription);

                $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Hello, I am your agent! The URL of your relay is ' . AcceptanceCase::relay_url()];
                $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Running with public key npub1'];
                $alices_expected_messages[] = ['EOSE', $subscriptionId];

                $request = $subscription();
                expect($request[2])->toBeArray();
                expect($request[2]['#p'])->toContain(NIP01TestFunctions::pubkey_recipient());

                $signed_message = Factory::event(NIP01TestFunctions::key_recipient(), 1, 'Hello!');
                $send($signed_message);
                $alices_expected_messages[] = ['OK', $signed_message()[1]['id'], true];
            });

            $alice_listen(function (Message $message, callable $stop) use ($unwrapper, &$alices_expected_messages, $data_dir) {
                $expected_message = array_shift($alices_expected_messages);

                $remaining = [];
                foreach ($alices_expected_messages as $expected_message) {
                    if ($expected_message[0] !== $message->type) {
                        $remaining[] = $expected_message;
                        continue;
                    }
                    switch ($message->type) {
                        case 'EVENT':
                            if ($message->payload[0] !== $expected_message[0]) {
                                $remaining[] = $expected_message;
                            } elseif ($unwrapper($message->payload[1]) !== $expected_message[1]) {
                                $remaining[] = $expected_message;
                            }
                            break;

                        default:
                            if ($message->payload !== $expected_message) {
                                $remaining[] = $expected_message;
                            }
                            break;

                    }

                }
                $alices_expected_messages = $remaining;
                if (count($alices_expected_messages) === 0) {
                    $stop();
                }
            });


            $bob_message = Factory::event(NIP01TestFunctions::key_sender(), 1, 'Hello!');

            $bobs_expected_messages = [];

            $bob_listen = $bob(function(callable $send) use ($bob_message, &$bobs_expected_messages) {
                $send($bob_message);
                $bobs_expected_messages[] = ['OK', $bob_message()[1]['id'], true, ''];
            });

            expect($bobs_expected_messages)->toHaveCount(1);

            $bob_listen(function (Message $message, callable $stop) use (&$bobs_expected_messages) {
                $expected_message = array_shift($bobs_expected_messages);

                $type = array_shift($expected_message);
                expect($message->type)->toBe($type, 'Message type checks out');
                expect($message->payload)->toBe($expected_message);

                if (count($bobs_expected_messages) === 0) {
                    $stop();
                }
            });


            $events = new nostriphant\Stores\Engine\SQLite(new SQLite3($data_dir . '/transpher.sqlite'), []);

            $notes_alice = iterator_to_array(nostriphant\Stores\Store::query($events, ['authors' => [NIP01TestFunctions::pubkey_recipient()], 'kinds' => [1]]));
            expect($notes_alice[0]->kind)->toBe(1);
            expect($notes_alice[0]->content)->toBe('Hello!');

            $notes_bob = iterator_to_array(nostriphant\Stores\Store::query($events, ['ids' => [$bob_message()[1]['id']]]));


            expect($notes_bob)->toHaveLength(1);
            expect($notes_bob[0]->kind)->toBe(1);
            expect($notes_bob[0]->content)->toBe('Hello!');
            
        } catch (\Exception $e) {
            $agent();
            $relay();
            throw $e;
        }

        $agent();
        $relay();
    });
});
