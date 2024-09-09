<?php

use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;
use WebSocket\Middleware\PingInterval;

function wrap_proces($process) : callable {

    return function() use ($process) {
        proc_terminate($process);
        proc_close($process);
        unset($process);
        return true;
    };
}

function redis_server(int $port, array $env) {
    $cmd = '/opt/homebrew/bin/redis-server --port ' . $port;
    $descriptorspec = [
        0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
        1 => ["file", dirname(dirname(__DIR__)) . "/logs/redis-output.log", "a"],  // stdout is a pipe that the child will write to
        2 => ["file", dirname(dirname(__DIR__)) . "/logs/redis-errors.log", "a"] // stderr is a file to write to
    ];

    $cwd = dirname(dirname(__DIR__));

    $process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);
    sleep(1); // allow server to start
    
    return wrap_proces($process);
}

function relay_server(int $port, array $env) : callable {
    $cmd = '/opt/homebrew/bin/php websocket.php ' . $port;
    $descriptorspec = [
        0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
        1 => ["file", dirname(dirname(__DIR__)) . "/logs/relay-$port-output.log", "w"],  // stdout is a pipe that the child will write to
        2 => ["file", dirname(dirname(__DIR__)) . "/logs/relay-$port-errors.log", "w"] // stderr is a file to write to
    ];

    $cwd = dirname(dirname(__DIR__));

    $process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);
    sleep(1); // allow server to start

    return wrap_proces($process);
}

function client(int $port) : \TranspherTests\Client {
    $client = new TranspherTests\Client("ws://127.0.0.1:" . $port);
    $client
        ->addMiddleware(new CloseHandler())
        ->addMiddleware(new PingResponder())
        ->addMiddleware(new PingInterval(interval: 30));
    return $client;
}
function generic_client() {
    return client(8081);
}

function sendSignedMessage(TranspherTests\Client $client, array $signed_message) {
    $client->expectNostrOK($signed_message[1]['id']);
    $client->connect();
    $client->json($signed_message);
    $client->start();
}

$server;
beforeAll(function() use (&$server) {
    $server = relay_server(8081, []);
});
afterAll(function() use (&$server) {
    $server();
});

describe('relay', function()  {
   
    it('accepts clients', function () {
        $alice = generic_client();
        $alice->connect();
        $bob = generic_client();
        $bob->connect();
        
        expect($alice->isConnected())->toBeTrue();
        expect($bob->isConnected())->toBeTrue();
    });
    
    it('responds with OK on simple events', function() {
        $alice = generic_client();
        
        $note = \Transpher\Message::event(1, 'Hello world!');
        $signed_note = $note(\Transpher\Key::generate());
        
        $alice->expectNostrOK($signed_note[1]['id']);
        
        $alice->connect();
        $alice->json($signed_note);
        $alice->start();
    });
    
    
    it('responds with a NOTICE on unsupported message types', function() {
        $alice = generic_client();
        
        $alice->expectNostrNotice('Message type UNKNOWN not supported');
        
        $alice->connect();
        $alice->json(['UNKNOWN', uniqid()]);
        $alice->start();
    });
    
    it('replies CLOSED on non-existing filters', function() {
        $alice = generic_client();
        $bob = generic_client();
        
        $note = \Transpher\Message::event(1, 'Hello world!');
        sendSignedMessage($alice, $note(\Transpher\Key::generate()));
       
        $subscription = Transpher\Message::subscribe();
        
        $bob->expectNostrNotice('Invalid message');
        
        $bob->json($subscription());
        $bob->start();
    });
    
    
    it('replies CLOSED on empty filters', function() {
        $alice = generic_client();
        $bob = generic_client();
        
        $note = \Transpher\Message::event(1, 'Hello world!');
        sendSignedMessage($alice, $note(\Transpher\Key::generate()));
       
        $subscription = Transpher\Message::subscribe();
        
        $bob->expectNostrClosed($subscription()[1], 'Subscription filters are empty');
        
        $request = $subscription();
        $request[] = [];
        $bob->json($request);
        $bob->start();
    });
    
    it('sends events to all clients subscribed on event id', function() {
        $alice = generic_client();
        $bob = generic_client();

        $note1 = \Transpher\Message::event(1, 'Hello worlda!');
        sendSignedMessage($alice, $note1(\Transpher\Key::generate()));
        
        $note2 = \Transpher\Message::event(1, 'Hello worldi!');
        $note2_signed = $note2(\Transpher\Key::generate());
        sendSignedMessage($alice, $note2_signed);
        
        $subscription = Transpher\Message::subscribe();
        
        $bob->expectNostrEvent($subscription()[1],'Hello worldi!');
        $bob->expectNostrEose($subscription()[1]);
        
        $bob->json(Transpher\Message::filter($subscription, ids:[$note2_signed[1]['id']])());
        $bob->start();
    });
    
    it('sends events to all clients subscribed on author (pubkey)', function() {
        $alice = generic_client();
        $bob = generic_client();

        $note = \Transpher\Message::event(1, 'Hello world!');
        $key = \Transpher\Key::generate();
        sendSignedMessage($alice, $note($key));
        
        $subscription = Transpher\Message::subscribe();
        
        $bob->expectNostrEvent($subscription()[1],'Hello world!');
        $bob->expectNostrEose($subscription()[1]);
        
        $request = Transpher\Message::filter($subscription, authors:[$key()])();
        $bob->json($request);
        $bob->start();
    });
    
    it('closes subscription and stop sending events to subscribers', function() {
        $alice = generic_client();
        $bob = generic_client();
        $key = \Transpher\Key::generate();

        $note = \Transpher\Message::event(1, 'Hello world!');
        sendSignedMessage($alice, $note($key));
        
        $subscription = Transpher\Message::subscribe();
        
        $bob->expectNostrEvent($subscription()[1],'Hello world!');
        $bob->expectNostrEose($subscription()[1]);
        
        $request = Transpher\Message::filter($subscription, authors:[$key()])();
        $bob->json($request);
        $bob->start();
        
        $bob->expectNostrClosed($subscription()[1], '');
        
        $request = Transpher\Message::close($subscription)();
        $bob->json($request);
        $bob->start();
    });
    
    it('sends events to all clients subscribed on author (pubkey), even after restarting the server', function() {
        $store_redis = 'redis://127.0.0.1:6379/1';
        
        $server = relay_server(8082, [
            'TRANSPHER_STORE' => $store_redis
        ]);
        
        $alice = client(8082);
        $bob = client(8082);

        $key = \Transpher\Key::generate();
        
        $note = \Transpher\Message::event(1, 'Hello wirld!');
        sendSignedMessage($alice, $note($key));
        
        expect($server())->toBe(true);
        
        $server = relay_server(8082, [
            'TRANSPHER_STORE' => $store_redis
        ]); // start server
        
        $subscription = Transpher\Message::subscribe();
        
        $bob->expectNostrEvent($subscription()[1],'Hello wirld!');
        $bob->expectNostrEose($subscription()[1]);
        
        $request = Transpher\Message::filter($subscription, authors:[$key()])();
        $bob->json($request);
        $bob->start();
        
        expect($server())->toBe(true);
    });
    
    it('uses redis as a back-end store', function() {
        $store_redis = 'redis://127.0.0.1:6379/1';
        
        $key_alice = \Transpher\Key::generate();
        $note = \Transpher\Message::event(1, 'Hello wirld!');
        
        $redis = new \Redis();
        
        expect($redis->connect('localhost', 6379, context:[]))->toBeTrue();
        $redis->select(1);
        
        $redis->flushDB();
        
        $note_request = $note($key_alice);
        $redis->rawCommand('JSON.set', $note_request[1]['id'], '$', json_encode($note_request[1]));
        
        expect($redis->dbSize())->toBe(1);
        $iterator = null;
        expect($redis->scan($iterator)[0])->toBe($note_request[1]['id']);
        // configure server to use store
        $relay = relay_server(8083, [
            'TRANSPHER_STORE' => $store_redis
        ]); // start server
        
        $bob = client(8083);
        $subscription = Transpher\Message::subscribe();
        $bob->expectNostrEvent($subscription()[1], 'Hello wirld!');
        $bob->expectNostrEose($subscription()[1]);
        
        $request = Transpher\Message::filter($subscription, authors:[$key_alice()])();
        $bob->json($request);
        $bob->start();
        
        $redis->flushDB();
        $relay();
    });
    
    it('sends events to all clients subscribed on kind', function() {
        $alice = generic_client();
        $bob = generic_client();

        $note = \Transpher\Message::event(3, 'Hello world!');
        sendSignedMessage($alice, $note(\Transpher\Key::generate()));
        
        $subscription = Transpher\Message::subscribe();
        
        $bob->expectNostrEvent($subscription()[1],'Hello world!');
        $bob->expectNostrEose($subscription()[1]);
        
        $bob->json(Transpher\Message::filter($subscription, kinds:[3])());
        $bob->start();
    });
    
    it('relays events to Bob after they subscribed on Alices messages', function() {
        $alice = generic_client();
        $bob = generic_client();
        $key_alice = \Transpher\Key::generate();
        
        $subscription = Transpher\Message::subscribe();
        
        $bob->expectNostrEose($subscription()[1]);
        
        $request = Transpher\Message::filter($subscription, authors:[$key_alice()])();
        $bob->json($request);
        $bob->start();
                
        $bob->expectNostrEvent($subscription()[1], 'Relayable Hello worlda!');
        $bob->expectNostrEose($subscription()[1]);
        
        $note1 = \Transpher\Message::event(1, 'Relayable Hello worlda!');
        sendSignedMessage($alice, $note1($key_alice));
        
        $note2 = \Transpher\Message::event(1, 'Hello worldi!');
        sendSignedMessage($alice, $note2(\Transpher\Key::generate()));
        
        $bob->start();
    });
    
});
