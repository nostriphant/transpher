<?php

use nostriphant\NIP01\Key;
use nostriphant\TranspherTests\Feature\Functions;
use nostriphant\Transpher\Relay\InformationDocument;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Subscription\Filter;
use nostriphant\NIP19\Bech32;
use nostriphant\NIP01\Nostr;

beforeAll(function () {
    global $relay;
    $agent_key = \Pest\key_recipient();

    $relay = Functions::bootRelay('127.0.0.1:8087', $env = [
        'AGENT_NSEC' => Bech32::toNsec($agent_key(Key::private())),
        'RELAY_URL' => 'ws://127.0.0.1:8087',
        'RELAY_OWNER_NPUB' => Bech32::toNpub(\Pest\pubkey_sender()),
        'RELAY_NAME' => 'Really relay',
        'RELAY_DESCRIPTION' => 'This is my dev relay',
        'RELAY_CONTACT' => 'transpher@nostriphant.dev',
        'RELAY_STORE' => ROOT_DIR . '/data/events/' . uniqid('relay_', true),
        'LIMIT_EVENT_CREATED_AT_LOWER_DELTA' => 60 * 60 * 72 // to accept NIP17 pdm created_at randomness
    ]);
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
        expect($response)->toBe(InformationDocument::generate('Really relay', 'This is my dev relay', Bech32::toNpub(\Pest\pubkey_sender()), 'transpher@nostriphant.dev'));
    });
});



describe('agent', function (): void {
    it('starts relay and sends private direct messsage to relay owner', function (): void {
        $agent = Functions::bootAgent(8084, [
            'RELAY_OWNER_NPUB' => Bech32::toNpub(Pest\pubkey_recipient()),
            'AGENT_NSEC' => Bech32::toNsec(Pest\pubkey_sender()),
            'RELAY_URL' => 'ws://127.0.0.1:8087'
        ]);
        sleep(1); // hack to give agent some time to boot...
        $alice = \nostriphant\TranspherTests\Client::client(8087);
        $subscription = Factory::subscribe(
                new Filter(tags: ['#p' => [Pest\pubkey_recipient()]])
        );
        $alice->expectNostrPrivateDirectMessage($subscription()[1], Pest\key_recipient(), 'Hello, I am your agent! The URL of your relay is ws://127.0.0.1:8087');
        $request = $subscription();
        $alice->json($request);
        expect($request[2])->toBeArray();
        expect($request[2]['#p'])->toContain(Pest\pubkey_recipient());

        $alice->start();

        $agent();
    });
});

describe('blossom support', function () {
    it('supports BUD-01 (GET /<sha-256>)', function () {
        $content = 'Hello World!';
        $hash = hash('sha256', $content);
        file_put_contents(ROOT_DIR . '/data/files/' . $hash, $content);
        expect(file_get_contents(ROOT_DIR . '/data/files/' . $hash))->toBe($content);

        $curl = curl_init('http://localhost:8087/' . $hash);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        expect($info['http_code'])->toBe(200);
        expect($info['content_type'])->toContain('text/plain');
        expect($body)->toBe($content);
    });

    it('supports BUD-01 (HEAD /<sha-256>)', function () {
        $content = 'Hello World!';
        $hash = hash('sha256', $content);
        file_put_contents(ROOT_DIR . '/data/files/' . $hash, $content);
        expect(file_get_contents(ROOT_DIR . '/data/files/' . $hash))->toBe($content);

        $curl = curl_init('http://localhost:8087/' . $hash);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
        $body = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        expect($info['http_code'])->toBe(200);
        expect($info['content_type'])->toContain('text/plain');
        expect($body)->toBeEmpty();
    });
});
