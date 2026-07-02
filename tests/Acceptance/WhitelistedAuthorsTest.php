<?php

use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use nostriphant\TranspherTests\Listener;
use nostriphant\TranspherTests\Transpher;

use nostriphant\Client\Client;

describe('only events from whitelisted authors/recipients are stored', function () {
    it('only stores messages from owner and agent, but they are still being delivered', function (string $sender_hex, string $recipient_hex) {
        $sender = Key::fromHex($sender_hex);
        $recipient = Key::fromHex($recipient_hex);

        $transpher = new Transpher('8088', $recipient, []);

        try {

            $worker = Amp\Parallel\Worker\createWorker();

            $bob_message = (new nostriphant\NIP01\Event\Unsigned(
                            created_at: time(),
                    kind: 1,
                    content: 'Hello!',
                    tags: []
            ))($sender);

            $executions = [
                // Alice
                $worker->submit(new nostriphant\TranspherTests\Acceptance\WhitelistedAuthorsTest\Alice($transpher->ws, $recipient_hex, Key::derivePublicKey($sender))),
                // Bob
                $worker->submit(new \nostriphant\TranspherTests\Acceptance\WhitelistedAuthorsTest\Bob($transpher->ws, $sender_hex, $bob_message))
            ];

            $responses = Amp\Future\await(array_map(
                fn (Amp\Parallel\Worker\Execution $e) => $e->getFuture(),
                $executions,
            ));

            $events = new nostriphant\Stores\Engine\SQLite(new SQLite3($transpher->data_directory . '/transpher.sqlite'), []);

            $notes_alice = iterator_to_array(nostriphant\Stores\Store::query($events, ['authors' => [Key::derivePublicKey($recipient)], 'kinds' => [1]]));
            expect($notes_alice[0]->kind)->toBe(1);
            expect($notes_alice[0]->content)->toBe('Hello!');

            $notes_bob = iterator_to_array(nostriphant\Stores\Store::query($events, ['ids' => [$bob_message->id]]));
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

        $transpher = new Transpher('8090', $recipient, [(string) Bech32::npub(Key::derivePublicKey($sender))]);

        $bob_message = (new nostriphant\NIP01\Event\Unsigned(
                        created_at: time(),
                        kind: 1,
                        content: 'Hello!',
                        tags: []
                ))($sender);

        try {
            $alice = new Client($transpher->ws);
            $bob = new Client($transpher->ws);

            $bob_listener = new Listener('bob-8090', $recipient);
            $bob(function(callable $send) use ($bob_listener, $bob_message) {
                Listener::expectOK($bob_listener, $send, $bob_message);
            });

            $alice_listener = new Listener('alice-8090', $recipient);
            $alice(function(callable $send, callable $subscribe) use ($alice_listener, $recipient, $transpher) {
                $subscription = $subscribe(...['#p' => [Key::derivePublicKey($recipient)]]);
                Listener::expectSubscription($alice_listener, $subscription,
                        'Hello, I am your agent! The URL of your relay is ' . $transpher->ws,
                        'Running with public key npub15fs4wgrm7sllg4m0rqd3tljpf5u9a2g6443pzz4fpatnvc9u24qsnd6036');

                Listener::expectOK($alice_listener, $send, (new nostriphant\NIP01\Event\Unsigned(
                                created_at: time(),
                                        kind: 1,
                                        content: 'Hello!',
                                        tags: []
                                ))($recipient));
            });

//            $bob_listen(function (Message $message, callable $stop) use (&$bobs_expected_messages) {
//                $expected_message = array_shift($bobs_expected_messages);
//
//                $type = array_shift($expected_message);
//                expect($message->type)->toBe($type, 'Message type checks out');
//                expect($message->payload)->toBe($expected_message);
//
//                if (count($bobs_expected_messages) === 0) {
//                    $stop();
//                }
//            });


            $events = new nostriphant\Stores\Engine\SQLite(new SQLite3($transpher->data_directory . '/transpher.sqlite'), []);

            $notes_alice = iterator_to_array(nostriphant\Stores\Store::query($events, ['authors' => [Key::derivePublicKey($recipient)], 'kinds' => [1]]));
            expect($notes_alice[0]->kind)->toBe(1);
            expect($notes_alice[0]->content)->toBe('Hello!');

            $notes_bob = iterator_to_array(nostriphant\Stores\Store::query($events, ['ids' => [$bob_message->id]]));


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
