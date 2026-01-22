<?php

use nostriphant\NIP01Tests\Functions as NIP01TestFunctions;
use nostriphant\NIP19\Bech32;
use nostriphant\TranspherTests\AcceptanceCase;
use nostriphant\TranspherTests\Factory;

use nostriphant\Client\Client;
use nostriphant\NIP01\Message;

$cleanup;
beforeAll(function() use (&$cleanup) {
    $data_dir = AcceptanceCase::data_dir('8087');
    (is_file($data_dir . '/transpher.sqlite') === false) ||  unlink($data_dir . '/transpher.sqlite');
    expect($data_dir . '/transpher.sqlite')->not()->toBeFile();
    
    $relay = AcceptanceCase::bootRelay(AcceptanceCase::relay_url('tcp://'), [
        'AGENT_NSEC' => (string) 'nsec1ffqhqzhulzesndu4npay9rn85kvwyfn8qaww9vsz689pyf5sfz7smpc6mn',
        'RELAY_URL' => AcceptanceCase::relay_url(),
        'RELAY_OWNER_NPUB' => (string) Bech32::npub(NIP01TestFunctions::pubkey_recipient()),
        'RELAY_NAME' => 'Really relay',
        'RELAY_DESCRIPTION' => 'This is my dev relay',
        'RELAY_CONTACT' => 'transpher@nostriphant.dev',
        'RELAY_DATA' => $data_dir,
        'RELAY_LOG_LEVEL' => 'DEBUG',
        'LIMIT_EVENT_CREATED_AT_LOWER_DELTA' => 60 * 60 * 72, // to accept NIP17 pdm created_at randomness
    ]);
    
    expect($relay)->toBeCallable('Relay is not callable');
    
    $agent = AcceptanceCase::bootAgent(8087, [
        'RELAY_OWNER_NPUB' => (string) Bech32::npub(NIP01TestFunctions::pubkey_recipient()),
        'AGENT_NSEC' => (string) 'nsec1ffqhqzhulzesndu4npay9rn85kvwyfn8qaww9vsz689pyf5sfz7smpc6mn',
        'RELAY_URL' => AcceptanceCase::relay_url(),
        'AGENT_LOG_LEVEL' => 'DEBUG',
    ]);
    expect($agent)->toBeCallable('Agent is not callable');
    
    sleep(3);
    
    $cleanup = function() use ($agent, $relay) {
        $agent();
        $relay();
    };
});


it('starts relay and sends private direct messsage to relay owner ('.NIP01TestFunctions::pubkey_recipient().')', function () {
    $data_dir = AcceptanceCase::data_dir('8087');
    
    $alices_expected_messages = [];
    $alice = Client::connectToUrl(AcceptanceCase::relay_url());
    $alice_log = AcceptanceCase::client_log('alice', NIP01TestFunctions::pubkey_recipient());
    
    $bob = Client::connectToUrl(AcceptanceCase::relay_url());
    $bob_log = AcceptanceCase::client_log('bob', NIP01TestFunctions::pubkey_sender());

    expect($alice)->toBeCallable('Alice is not callable');

    $unwrapper = AcceptanceCase::unwrap(NIP01TestFunctions::key_recipient());

    $alice_listen = $alice(function(callable $send) use (&$alices_expected_messages) {
        $subscription = Factory::subscribe(['#p' => [NIP01TestFunctions::pubkey_recipient()]]);

        $subscriptionId = $subscription()[1];
        $send($subscription);

        $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Hello, I am your agent! The URL of your relay is ' . AcceptanceCase::relay_url()];
        $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Running with public key npub15fs4wgrm7sllg4m0rqd3tljpf5u9a2g6443pzz4fpatnvc9u24qsnd6036'];
        $alices_expected_messages[] = ['EOSE', $subscriptionId];

        $request = $subscription();
        expect($request[2])->toBeArray();
        expect($request[2]['#p'])->toContain(NIP01TestFunctions::pubkey_recipient());

        $signed_message = Factory::event(NIP01TestFunctions::key_recipient(), 1, 'Hello!');
        $send($signed_message);
        $alices_expected_messages[] = ['OK', $signed_message()[1]['id'], true, ""];
    });

    expect($alice_listen)->toBeCallable('Alice listen is not callable');

    $alice_listen(AcceptanceCase::createListener($unwrapper, $alices_expected_messages, $data_dir, $alice_log));

    $bob_message = Factory::event(NIP01TestFunctions::key_sender(), 1, 'Hello!');

    $bobs_expected_messages = [];

    expect($bob)->toBeCallable('Bob is not callable');

    $bob_listen = $bob(function(callable $send) use ($bob_message, &$bobs_expected_messages) {
        $send($bob_message);
        $bobs_expected_messages[] = ['OK', $bob_message()[1]['id'], true, ''];

        $send(Message::req('sddf', ["kinds" => [1059], "#p" => ["ca447ffbd98356176bf1a1612676dbf744c2335bb70c1bc9b68b122b20d6eac6"]]));
        $bobs_expected_messages[] = ['EOSE', 'sddf'];
    });

    expect($bobs_expected_messages)->toHaveCount(2);
    expect($bob_listen)->toBeCallable('Bob listen is not callable');

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

    $pdms = iterator_to_array(nostriphant\Stores\Store::query($events, ['#p' => [NIP01TestFunctions::pubkey_recipient()]]));
    expect($pdms[0]->kind)->toBe(1059);

    expect(file_get_contents(ROOT_DIR . '/logs/relay-6c0de3-output.log'))->not()->toContain('ERROR');
});

afterAll(function() use (&$cleanup) {
    $cleanup();
});