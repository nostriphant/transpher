<?php

use nostriphant\NIP01\Key;
use nostriphant\TranspherTests\Feature\Functions;
use nostriphant\Transpher\Relay\InformationDocument;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Nostr;
use nostriphant\NIP59\Gift;
use nostriphant\NIP01\Event;
use nostriphant\NIP59\Seal;
use nostriphant\NIP01\Message;

beforeAll(function () {
    global $relay, $env, $data_dir, $relay_url;
    $relay_url = fn(string $scheme = 'ws://') => $scheme . '127.0.0.1:8087';

    $data_dir = ROOT_DIR . '/data/' . uniqid('relay_', true);
    is_dir($data_dir) || mkdir($data_dir);

    $event = Pest\event(['id' => uniqid()]);
    is_dir($data_dir . '/events') || mkdir($data_dir . '/events');
    $event_file = $data_dir . '/events' . DIRECTORY_SEPARATOR . $event->id . '.php';
    file_put_contents($event_file, '<?php return ' . var_export($event, true) . ';');

    $relay = Functions::bootRelay($relay_url(''), $env = [
        'AGENT_NSEC' => (string) Bech32::nsec(\Pest\key_sender()(Key::private())),
        'RELAY_URL' => $relay_url(),
        'RELAY_OWNER_NPUB' => (string) Bech32::npub(\Pest\pubkey_recipient()),
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
        expect($response)->toBe(InformationDocument::generate('Really relay', 'This is my dev relay', \Pest\pubkey_recipient(), 'transpher@nostriphant.dev'));
    });
});



describe('agent', function (): void {
    it('starts relay and sends private direct messsage to relay owner', function (): void {
        global $data_dir, $relay_url;

        $agent = Functions::bootAgent(8084, [
            'RELAY_OWNER_NPUB' => (string) Bech32::npub(Pest\pubkey_recipient()),
            'AGENT_NSEC' => (string) Bech32::nsec(Pest\key_sender()(Key::private())),
            'RELAY_URL' => $relay_url()
        ]);
        sleep(1); // hack to give agent some time to boot...
        $alice = new \nostriphant\Transpher\Client($relay_url());
        $subscription = Factory::subscribe(['#p' => [Pest\pubkey_recipient()]]);

        $subscriptionId = $subscription()[1];
        $recipient_key = Pest\key_recipient();

        $expected_messages = [];
        $alice->onJson(function (callable $stop, Message $message) use (&$expected_messages) {
            $expected_message = array_shift($expected_messages);
            expect($message->type)->toBe($expected_message[0], 'Message type checks out');
            $expected_message[1]($message->payload);

            if (count($expected_messages) === 0) {
                $stop();
            }
        });

        $expected_messages[] = ['EVENT', function (array $payload) use ($subscriptionId, $recipient_key, $relay_url) {
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
            expect($private_message->content)->toBe('Hello, I am your agent! The URL of your relay is ' . $relay_url());
        }];
        $expected_messages[] = ['EOSE', function (array $payload) use ($subscriptionId) {
                expect($payload[0])->toBe($subscriptionId);
        }];

        $request = $subscription();
        $alice->json($request);
        expect($request[2])->toBeArray();
        expect($request[2]['#p'])->toContain(Pest\pubkey_recipient());

        $signed_message = Factory::event(\Pest\key_recipient(), 1, 'Hello!');
        $expected_messages[] = ['OK', function (array $payload) use ($signed_message) {
                expect($payload[0])->toBe($signed_message()[1]['id']);
            expect($payload[1])->toBeTrue();
        }];
        $alice->send($signed_message);
        $alice->start(5);

        $events = new nostriphant\Transpher\Stores\SQLite(new SQLite3($data_dir . '/transpher.sqlite'), []);

        $events(['authors' => [Pest\pubkey_recipient()]])(\nostriphant\Transpher\Stores\Results::copyTo($notes));
        expect($notes[0]->kind)->toBe(1);
        expect($notes[0]->content)->toBe('Hello!');

        $events(['#p' => [Pest\pubkey_recipient()]])(\nostriphant\Transpher\Stores\Results::copyTo($pdms));
        expect($pdms[0]->kind)->toBe(1059);

        $agent();
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
