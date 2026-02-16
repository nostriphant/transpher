<?php

use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use nostriphant\TranspherTests\AcceptanceCase;
use nostriphant\TranspherTests\Factory;

use nostriphant\Client\Client;
use nostriphant\NIP01\Message;

describe('only events from whitelisted authors/recipients are stored', function () {
    it('only stores messages from owner and agent, but they are still being delivered', function (string $sender_hex, string $recipient_hex) {
        $sender = Key::fromHex($sender_hex);
        $recipient = Key::fromHex($recipient_hex);

        $data_dir = AcceptanceCase::data_dir('8088');
        
        $transpher = AcceptanceCase::start_transpher('8088', $data_dir, $recipient, []);

        try {
            $alices_expected_messages = [];
            $alice = Client::connectToUrl(AcceptanceCase::relay_url(port:'8088'));
            $bob = Client::connectToUrl(AcceptanceCase::relay_url(port:'8088'));
            
            $alice_log = AcceptanceCase::client_log('alice-8088', $recipient(Key::public()));
            $bob_log = AcceptanceCase::client_log('bob-8088', $sender(Key::public()));

            $unwrapper = AcceptanceCase::unwrap($recipient);
            $subscriptionAlice = Factory::subscribe(['#p' => [$recipient(Key::public())]]);

            $bob_message = Factory::event($sender, 1, 'Hello!');
            $subscriptionAliceOnBobsMessage = Factory::subscribe(['ids' => [$bob_message()[1]['id']]]);
            $bobs_expected_messages = [['OK', $bob_message()[1]['id'], true, '']];
            
            $bob_listen;
            $alice_listen = $alice(function(callable $send) use (&$alices_expected_messages, $subscriptionAlice, $subscriptionAliceOnBobsMessage, $bob, $bob_message, &$bob_listen, $recipient) {

                $send($subscriptionAliceOnBobsMessage);
                $alices_expected_messages[] = ['EVENT', $subscriptionAliceOnBobsMessage()[1], 'Hello!'];
                $alices_expected_messages[] = ['EOSE', $subscriptionAliceOnBobsMessage()[1]];
                
                $subscriptionId = $subscriptionAlice()[1];
                $send($subscriptionAlice);

                $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Hello, I am your agent! The URL of your relay is ' . AcceptanceCase::relay_url(port:'8088')];
                $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Running with public key npub15fs4wgrm7sllg4m0rqd3tljpf5u9a2g6443pzz4fpatnvc9u24qsnd6036'];
                $alices_expected_messages[] = ['EOSE', $subscriptionId];

                $request = $subscriptionAlice();
                expect($request[2])->toBeArray();
                expect($request[2]['#p'])->toContain($recipient(Key::public()));

                $signed_message = Factory::event($recipient, 1, 'Hello!');
                $send($signed_message);
                
                $alices_expected_messages[] = ['OK', $signed_message()[1]['id'], true, ""];
                
                sleep(1);
                
                $bob_listen = $bob(fn(callable $send) => $send($bob_message));
                
            });
            

            expect($bobs_expected_messages)->toHaveCount(1);
            
            $alice_listen(AcceptanceCase::createListener($unwrapper, $alices_expected_messages, $data_dir, $alice_log));
            $bob_listen(AcceptanceCase::createListener($unwrapper, $bobs_expected_messages, $data_dir, $bob_log));

            $events = new nostriphant\Stores\Engine\SQLite(new SQLite3($data_dir . '/transpher.sqlite'), []);

            $notes_alice = iterator_to_array(nostriphant\Stores\Store::query($events, ['authors' => [$recipient(Key::public())], 'kinds' => [1]]));
            expect($notes_alice[0]->kind)->toBe(1);
            expect($notes_alice[0]->content)->toBe('Hello!');

            $notes_bob = iterator_to_array(nostriphant\Stores\Store::query($events, ['ids' => [$bob_message()[1]['id']]]));
            expect($notes_bob)->toHaveLength(0);
        } catch (\Exception $e) {
            $transpher();
            throw $e;
        }

        $transpher();
    })->with([
        ['a71a415936f2dd70b777e5204c57e0df9a6dffef91b3c78c1aa24e54772e33c3', '6eeb5ad99e47115467d096e07c1c9b8b41768ab53465703f78017204adc5b0cc']
    ]);
    
    
    it('stores messages from owner, agent and whitelisted', function (string $sender_hex, string $recipient_hex) {
        $sender = Key::fromHex($sender_hex);
        $recipient = Key::fromHex($recipient_hex);
        
        $data_dir = AcceptanceCase::data_dir('8090');
        
        $transpher = AcceptanceCase::start_transpher('8090', $data_dir, $recipient, [(string) Bech32::npub($sender(Key::public()))]);

        try {
            $alices_expected_messages = [];
            $alice = Client::connectToUrl(AcceptanceCase::relay_url(port:'8090'));
            $bob = Client::connectToUrl(AcceptanceCase::relay_url(port:'8090'));
            
            $alice_log = AcceptanceCase::client_log('alice-8090', $recipient(Key::public()));

            $unwrapper = AcceptanceCase::unwrap($recipient);

            $alice_listen = $alice(function(callable $send) use (&$alices_expected_messages, $recipient) {
                $subscription = Factory::subscribe(['#p' => [$recipient(Key::public())]]);

                $subscriptionId = $subscription()[1];
                $send($subscription);

                $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Hello, I am your agent! The URL of your relay is ' . AcceptanceCase::relay_url(port:'8090')];
                $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Running with public key npub15fs4wgrm7sllg4m0rqd3tljpf5u9a2g6443pzz4fpatnvc9u24qsnd6036'];
                $alices_expected_messages[] = ['EOSE', $subscriptionId];

                $request = $subscription();
                expect($request[2])->toBeArray();
                expect($request[2]['#p'])->toContain($recipient(Key::public()));

                $signed_message = Factory::event($recipient, 1, 'Hello!');
                $send($signed_message);
                $alices_expected_messages[] = ['OK', $signed_message()[1]['id'], true, ""];
            });

            $alice_listen(AcceptanceCase::createListener($unwrapper, $alices_expected_messages, $data_dir, $alice_log));


            $bob_message = Factory::event($sender, 1, 'Hello!');

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

            $notes_alice = iterator_to_array(nostriphant\Stores\Store::query($events, ['authors' => [$recipient(Key::public())], 'kinds' => [1]]));
            expect($notes_alice[0]->kind)->toBe(1);
            expect($notes_alice[0]->content)->toBe('Hello!');

            $notes_bob = iterator_to_array(nostriphant\Stores\Store::query($events, ['ids' => [$bob_message()[1]['id']]]));


            expect($notes_bob)->toHaveLength(1);
            expect($notes_bob[0]->kind)->toBe(1);
            expect($notes_bob[0]->content)->toBe('Hello!');
            
        } catch (\Exception $e) {
            $transpher();
            throw $e;
        }

        $transpher();
    })->with([
        ['a71a415936f2dd70b777e5204c57e0df9a6dffef91b3c78c1aa24e54772e33c3', '6eeb5ad99e47115467d096e07c1c9b8b41768ab53465703f78017204adc5b0cc']
    ]);
    
});
