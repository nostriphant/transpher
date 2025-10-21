<?php

use nostriphant\NIP01Tests\Functions as NIP01TestFunctions;
use nostriphant\TranspherTests\Feature\Functions;
use nostriphant\Transpher\Relay\InformationDocument;
use nostriphant\TranspherTests\Factory;

use nostriphant\Client\Client;
use nostriphant\NIP01\Event;
use nostriphant\NIP01\Nostr;
use nostriphant\NIP19\Bech32;
use nostriphant\NIP59\Gift;
use nostriphant\NIP59\Seal;
use nostriphant\NIP01\Message;

beforeAll(function () {
    global $relay, $env, $data_dir, $relay_url;
    $relay_url = fn(string $scheme = 'ws://') => $scheme . '127.0.0.1:8087';

    $data_dir = ROOT_DIR . '/data/' . uniqid('relay_', true);
    is_dir($data_dir) || mkdir($data_dir);

    $event = call_user_func(new nostriphant\NIP59\Rumor(
                    created_at: time(),
                    pubkey: NIP01TestFunctions::pubkey_recipient(),
                    kind: 3,
                    content: '',
                    tags: [
                        ["p", NIP01TestFunctions::pubkey_sender(), $relay_url(), "bob"],
                    ]
            ), NIP01TestFunctions::key_recipient());
    is_dir($data_dir . '/events') || mkdir($data_dir . '/events');
    $event_file = $data_dir . '/events' . DIRECTORY_SEPARATOR . $event->id . '.php';
    file_put_contents($event_file, '<?php return ' . var_export($event, true) . ';');

    $relay = Functions::bootRelay($relay_url(''), $env = [
        'AGENT_NSEC' => (string) 'nsec1ffqhqzhulzesndu4npay9rn85kvwyfn8qaww9vsz689pyf5sfz7smpc6mn',
        'RELAY_URL' => $relay_url(),
        'RELAY_OWNER_NPUB' => (string) Bech32::npub(NIP01TestFunctions::pubkey_recipient()),
        'RELAY_NAME' => 'Really relay',
        'RELAY_DESCRIPTION' => 'This is my dev relay',
        'RELAY_CONTACT' => 'transpher@nostriphant.dev',
        'RELAY_DATA' => $data_dir,
        'RELAY_LOG_LEVEL' => 'DEBUG',
        'LIMIT_EVENT_CREATED_AT_LOWER_DELTA' => 60 * 60 * 72 // to accept NIP17 pdm created_at randomness
    ]);

    expect($event_file)->not()->toBeFile();
    rmdir($data_dir . '/events/');
});

afterAll(function () {
    global $relay;

    $relay();
});

describe('relay', function () {
    it('sends an information document (NIP-11), when on a HTTP request', function () {
        global $relay_url;
        $curl = curl_init($relay_url('http://'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept: application/nostr+json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $responseText = curl_exec($curl);
        expect($responseText)->not()->toBeFalse('['. curl_errno($curl).'] ' . curl_error($curl));
        expect($responseText)->not()->toContain('<b>Warning</b>');
        $response = Nostr::decode($responseText);

        expect($response)->not()->toBeNull($responseText);
        expect($response)->toBe(InformationDocument::generate('Really relay', 'This is my dev relay', NIP01TestFunctions::pubkey_recipient(), 'transpher@nostriphant.dev'));
    });
});



describe('agent', function (): void {
    it('starts relay and sends private direct messsage to relay owner ('.NIP01TestFunctions::pubkey_recipient().')', function (): void {
        global $data_dir, $relay_url;

        $agent = Functions::bootAgent(8084, [
            'RELAY_OWNER_NPUB' => (string) Bech32::npub(NIP01TestFunctions::pubkey_recipient()),
            'AGENT_NSEC' => (string) 'nsec1ffqhqzhulzesndu4npay9rn85kvwyfn8qaww9vsz689pyf5sfz7smpc6mn',
            'RELAY_URL' => $relay_url()
        ]);
        
        sleep(3);
        
        $alices_expected_messages = [];
        $alice = Client::connectToUrl($relay_url());
        
        expect($alice)->toBeCallable('Alice is not callable');

        $unwrapper = Functions::unwrapper(NIP01TestFunctions::key_recipient());
        
        $alice_listen = $alice(function(callable $send) use ($relay_url, &$alices_expected_messages) {
            $subscription = Factory::subscribe(['#p' => [NIP01TestFunctions::pubkey_recipient()]]);

            $subscriptionId = $subscription()[1];
            $send($subscription);
            
            $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Hello, I am your agent! The URL of your relay is ' . $relay_url()];
            $alices_expected_messages[] = ['EVENT', $subscriptionId, 'Running with public key npub1'];
            $alices_expected_messages[] = ['EOSE', $subscriptionId];
                        
            $request = $subscription();
            expect($request[2])->toBeArray();
            expect($request[2]['#p'])->toContain(NIP01TestFunctions::pubkey_recipient());

            $signed_message = Factory::event(NIP01TestFunctions::key_recipient(), 1, 'Hello!');
            $send($signed_message);
            $alices_expected_messages[] = ['OK', $signed_message()[1]['id'], true];
        });
        
        expect($alice_listen)->toBeCallable('Alice listen is not callable');
        
        $alice_listen(function (Message $message, callable $stop) use ($unwrapper, &$alices_expected_messages) {
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
        
        $bob = Client::connectToUrl($relay_url());
        
        expect($bob)->toBeCallable('Bob is not callable');
        
        $bob_listen = $bob(function(callable $send) use ($bob_message, &$bobs_expected_messages) {
            $send($bob_message);
            $bobs_expected_messages[] = ['OK', $bob_message()[1]['id'], true, ''];

            $send(Message::req('sddf', [["kinds" => [1059], "#p" => ["ca447ffbd98356176bf1a1612676dbf744c2335bb70c1bc9b68b122b20d6eac6"]]]));
            $bobs_expected_messages[] = ['EOSE', 'sddf'];
        });
        
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
        
        expect($agent)->toBeCallable('Agent is not callable');

        $agent();

        $events = new nostriphant\Stores\Engine\SQLite(new SQLite3($data_dir . '/transpher.sqlite'), []);

        $notes_alice = iterator_to_array(nostriphant\Stores\Store::query($events, ['authors' => [NIP01TestFunctions::pubkey_recipient()], 'kinds' => [1]]));
        expect($notes_alice[0]->kind)->toBe(1);
        expect($notes_alice[0]->content)->toBe('Hello!');

        $notes_bob = iterator_to_array(nostriphant\Stores\Store::query($events, ['ids' => [$bob_message()[1]['id']]]));
        expect($notes_bob[0]->kind)->toBe(1);
        expect($notes_bob[0]->content)->toBe('Hello!');

        $pdms = iterator_to_array(nostriphant\Stores\Store::query($events, ['#p' => [NIP01TestFunctions::pubkey_recipient()]]));
        expect($pdms[0]->kind)->toBe(1059);

        expect(file_get_contents(ROOT_DIR . '/logs/relay-8087-output.log'))->not()->toContain('ERROR');
    });
});

describe('blossom support', function () {

    function write(string $content) {
        global $env;
        $hash = hash('sha256', $content);
        file_put_contents($env['RELAY_DATA'] . '/files/' . $hash, $content);
        expect($env['RELAY_DATA'] . '/files/' . $hash)->toBeFile();
        expect(file_get_contents($env['RELAY_DATA'] . '/files/' . $hash))->toBe($content);
        return $hash;
    }

    it('supports BUD-01 (GET /<sha-256>)', function () {
        global $relay_url;
        $hash = write('Hello World!');
        $curl = curl_init($relay_url('http://') . '/' . $hash);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        expect($info['http_code'])->toBe(200);
        expect($info['content_type'])->toContain('text/plain');
        expect($body)->toBe('Hello World!');
    });

    it('supports BUD-01 (HEAD /<sha-256>)', function () {
        global $relay_url;
        $hash = write('Hello World!');
        $curl = curl_init($relay_url('http://') . '/' . $hash);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $body = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        expect($info['http_code'])->toBe(200);
        expect($info['content_type'])->toContain('text/plain');
        expect($body)->toBeEmpty();
    });
});
