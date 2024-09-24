<?php

use \Transpher\Key;
use \Transpher\Message;
use \TranspherTests\Client;

function redis_server(int $port, array $env) {
    $cmd = ['/opt/homebrew/bin/redis-server', '--port', $port];
    return Transpher\Process::start('redis', $cmd, $env);
}

$main_relay;
beforeAll(function() use (&$main_relay) {
    \Transpher\Nostr\Relay::boot(8081, [], function (callable $relay) use (&$main_relay) {
        $main_relay = $relay;
    });
});
afterAll(function() use (&$main_relay) {
    $main_relay();
});

describe('relay', function () {
    it('accepts clients', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        expect($alice->connect())->toBeTrue();
        expect($bob->connect())->toBeTrue();
    });

    it('responds with OK on simple events', function () {
        $alice = Client::generic_client();

        $note = Message::event(1, 'Hello world!');
        $signed_note = $note(Key::generate());

        $alice->expectNostrOK($signed_note[1]['id']);

        $alice->json($signed_note);
        $alice->start();
    });

    it('responds with a NOTICE on unsupported message types', function () {
        $alice = Client::generic_client();

        $alice->expectNostrNotice('Message type UNKNOWN not supported');

        $alice->json(['UNKNOWN', uniqid()]);
        $alice->start();
    });

    it('replies CLOSED on non-existing filters', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        $note = Message::event(1, 'Hello world!');
        $alice->sendSignedMessage($note(Key::generate()));

        $bob->expectNostrNotice('Invalid message');
        $subscription = Message::subscribe();
        $bob->json($subscription());
        $bob->start();
    });

    it('replies CLOSED on empty filters', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        $note = Message::event(1, 'Hello world!');
        $alice->sendSignedMessage($note(Key::generate()));

        $subscription = Message::subscribe();

        $bob->expectNostrClosed($subscription()[1], 'Subscription filters are empty');
        $request = $subscription();
        $request[] = [];
        $bob->json($request);
        $bob->start();
    });

    it('sends events to all clients subscribed on event id', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        $note1 = Message::event(1, 'Hello worlda!');
        $alice->sendSignedMessage($note1(Key::generate()));

        $note2 = Message::event(1, 'Hello worldi!');
        $note2_signed = $note2(Key::generate());
        $alice->sendSignedMessage($note2_signed);

        $subscription = Message::subscribe();

        $bob->expectNostrEvent($subscription()[1], 'Hello worldi!');
        $bob->expectNostrEose($subscription()[1]);
        $bob->json(Message::filter($subscription, ids: [$note2_signed[1]['id']])());
        $bob->start();
    });

    it('sends events to all clients subscribed on author (pubkey)', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        $note = Message::event(1, 'Hello world!');
        $key = Key::generate();
        $alice->sendSignedMessage($note($key));
        $subscription = Message::subscribe();

        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $request = Message::filter($subscription, authors: [$key(Key::public())])();
        $bob->json($request);
        $bob->start();
    });

    it('sends events to all clients subscribed on p-tag', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        $note = Message::event(1, 'Hello world!', ['p', 'randomPTag']);
        $key = Key::generate();
        $alice->sendSignedMessage($note($key));
        $subscription = Message::subscribe();

        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $request = Message::filter($subscription, tags: ['#p' => ['randomPTag']])();
        $bob->json($request);
        $bob->start();
    });
    
    it('closes subscription and stop sending events to subscribers', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();
        $key = Key::generate();

        $note = Message::event(1, 'Hello world!');
        $alice->sendSignedMessage($note($key));

        $subscription = Message::subscribe();
        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $request = Message::filter($subscription, authors: [$key(Key::public())])();
        $bob->json($request);
        $bob->start();

        $bob->expectNostrClosed($subscription()[1], '');

        $request = Message::close($subscription)();
        $bob->json($request);
        $bob->start();
    });

    it('sends events to all clients subscribed on author (pubkey), even after restarting the server', function () {
        $env = [
            'TRANSPHER_STORE' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid()
        ];
        mkdir($env['TRANSPHER_STORE']);
        
        
        \Transpher\Nostr\Relay::boot(8082, $env, function (callable $server) use ($env) {
            $alice = Client::client(8082);

            $key = Key::generate();

            $note = Message::event(1, 'Hello wirld!');
            $alice->sendSignedMessage($note($key));

            $status = $server();
            expect($status)->toBeArray();
            expect($status['running'])->toBeFalse();

            \Transpher\Nostr\Relay::boot(8082, $env, function (callable $server) use ($key) {
                $bob = Client::client(8082);
                $subscription = Message::subscribe();

                $bob->expectNostrEvent($subscription()[1], 'Hello wirld!');
                $bob->expectNostrEose($subscription()[1]);

                $request = Message::filter($subscription, authors: [$key(Key::public())])();
                $bob->json($request);
                $bob->start();

                $status = $server();
                expect($status)->toBeArray();
                expect($status['running'])->toBeFalse();
            });
        });
    });

    it('uses redis as a back-end store', function () {
        $store_redis = 'redis://127.0.0.1:6379/1';

        $key_alice = Key::generate();
        $note = Message::event(1, 'Hello wirld!');

        $redis = new \Redis();

        expect($redis->connect('localhost', 6379, context: []))->toBeTrue();
        $redis->select(1);

        $redis->flushDB();

        $note_request = $note($key_alice);
        $redis->rawCommand('JSON.set', $note_request[1]['id'], '$', json_encode($note_request[1]));

        expect($redis->dbSize())->toBe(1);
        $iterator = null;
        expect($redis->scan($iterator)[0])->toBe($note_request[1]['id']);
        // configure server to use store

        $env = [
            'TRANSPHER_STORE' => $store_redis
        ];

        \Transpher\Nostr\Relay::boot(8083, $env, function (callable $relay) use ($redis, $key_alice) {
            $bob = Client::client(8083);
            $subscription = Message::subscribe();
            $bob->expectNostrEvent($subscription()[1], 'Hello wirld!');
            $bob->expectNostrEose($subscription()[1]);

            $request = Message::filter($subscription, authors: [$key_alice(Key::public())])();
            $bob->json($request);
            $bob->start();

            $redis->flushDB();
            $relay();
        });
    });

    it('sends events to all clients subscribed on kind', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        $note = Message::event(3, 'Hello world!');
        $alice->sendSignedMessage($note(Key::generate()));

        $subscription = Message::subscribe();

        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $bob->json(Message::filter($subscription, kinds: [3])());
        $bob->start();
    });

    it('relays events to Bob after they subscribed on Alices messages', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();
        $key_alice = Key::generate();

        $subscription = Message::subscribe();

        $bob->expectNostrEose($subscription()[1]);

        $request = Message::filter($subscription, authors: [$key_alice(Key::public())])();
        $bob->json($request);
        $bob->start();
        $bob->expectNostrEvent($subscription()[1], 'Relayable Hello worlda!');
        $bob->expectNostrEose($subscription()[1]);

        $note1 = Message::event(1, 'Relayable Hello worlda!');
        $alice->sendSignedMessage($note1($key_alice));

        $note2 = Message::event(1, 'Hello worldi!');
        $alice->sendSignedMessage($note2(Key::generate()));

        $bob->start(function () {

        });
    });
});
