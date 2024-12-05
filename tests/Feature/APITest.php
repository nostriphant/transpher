<?php

use nostriphant\NIP01\Key;
use nostriphant\TranspherTests\Feature\Functions;
use nostriphant\Transpher\Relay\InformationDocument;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Nostr;
use nostriphant\Transpher\Nostr\Subscription;

beforeAll(function () {
    global $relay, $env, $data_dir;
    $data_dir = ROOT_DIR . '/data/' . uniqid('relay_', true);
    is_dir($data_dir) || mkdir($data_dir);

    $event = Pest\event(['id' => uniqid()]);
    is_dir($data_dir . '/events') || mkdir($data_dir . '/events');
    $event_file = $data_dir . '/events' . DIRECTORY_SEPARATOR . $event->id . '.php';
    file_put_contents($event_file, '<?php return ' . var_export($event, true) . ';');

    $relay = Functions::bootRelay('127.0.0.1:8087', $env = [
        'AGENT_NSEC' => Bech32::toNsec(\Pest\key_sender()(Key::private())),
        'RELAY_URL' => 'ws://127.0.0.1:8087',
        'RELAY_OWNER_NPUB' => Bech32::toNpub(\Pest\pubkey_recipient()),
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
        $curl = curl_init('http://localhost:8087');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept: application/nostr+json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $responseText = curl_exec($curl);
        expect($responseText)->not()->toBeFalse('['. curl_errno($curl).'] ' . curl_error($curl));
        expect($responseText)->not()->toContain('<b>Warning</b>');
        $response = Nostr::decode($responseText);

        expect($response)->not()->toBeNull($responseText);
        expect($response)->toBe(InformationDocument::generate('Really relay', 'This is my dev relay', Bech32::toNpub(\Pest\pubkey_recipient()), 'transpher@nostriphant.dev'));
    });
});



describe('agent', function (): void {
    it('starts relay and sends private direct messsage to relay owner', function (): void {
        global $data_dir;
        $agent = Functions::bootAgent(8084, [
            'RELAY_OWNER_NPUB' => Bech32::toNpub(Pest\pubkey_recipient()),
            'AGENT_NSEC' => Bech32::toNsec(Pest\key_sender()(Key::private())),
            'RELAY_URL' => 'ws://127.0.0.1:8087'
        ]);
        sleep(1); // hack to give agent some time to boot...
        $alice = \nostriphant\TranspherTests\Client::client(8087);
        $subscription = Factory::subscribe(['#p' => [Pest\pubkey_recipient()]]);
        $alice->expectNostrPrivateDirectMessage($subscription()[1], Pest\key_recipient(), 'Hello, I am your agent! The URL of your relay is ws://127.0.0.1:8087');
        $request = $subscription();
        $alice->json($request);
        expect($request[2])->toBeArray();
        expect($request[2]['#p'])->toContain(Pest\pubkey_recipient());

        $alice->sendSignedMessage(Factory::event(\Pest\key_recipient(), 1, 'Hello!'));

        $events = new nostriphant\Transpher\Stores\SQLite(new SQLite3($data_dir . '/transpher.sqlite'), Subscription::make([]));

        $messages = iterator_to_array($events(Subscription::make(['authors' => [Pest\pubkey_recipient()]])));
        expect($messages[0]->kind)->toBe(1);
        expect($messages[0]->content)->toBe('Hello!');

        $messages = iterator_to_array($events(Subscription::make(['#p' => [Pest\pubkey_recipient()]])));
        expect($messages[0]->kind)->toBe(1059);

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
        $hash = write('Hello World!');
        $curl = curl_init('http://localhost:8087/' . $hash);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        expect($info['http_code'])->toBe(200);
        expect($info['content_type'])->toContain('text/plain');
        expect($body)->toBe('Hello World!');
    });

    it('supports BUD-01 (HEAD /<sha-256>)', function () {
        $hash = write('Hello World!');
        $curl = curl_init('http://localhost:8087/' . $hash);
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
