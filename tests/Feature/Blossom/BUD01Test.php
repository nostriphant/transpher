<?php

use nostriphant\Transpher\Nostr\Key;
use nostriphant\TranspherTests\Feature\Functions;

it('supports BUD-01 (GET /<sha-256>)', function () {
    $owner_key = \Pest\key_sender();
    $agent_key = \Pest\key_recipient();

    $relay = Functions::bootRelay('127.0.0.1:8087', $env = [
        'AGENT_NSEC' => $agent_key(Key::private(\nostriphant\Transpher\Nostr\Key\Format::BECH32)),
        'RELAY_URL' => 'ws://127.0.0.1:8087',
        'RELAY_OWNER_NPUB' => $owner_key(Key::public(\nostriphant\Transpher\Nostr\Key\Format::BECH32)),
        'RELAY_NAME' => 'Really relay',
        'RELAY_DESCRIPTION' => 'This is my dev relay',
        'RELAY_CONTACT' => 'nostr@rikmeijer.nl'
    ]);

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

    $relay();
});


it('supports BUD-01 (HEAD /<sha-256>)', function () {
    $owner_key = \Pest\key_sender();
    $agent_key = \Pest\key_recipient();

    $relay = Functions::bootRelay('127.0.0.1:8087', $env = [
        'AGENT_NSEC' => $agent_key(Key::private(\nostriphant\Transpher\Nostr\Key\Format::BECH32)),
        'RELAY_URL' => 'ws://127.0.0.1:8087',
        'RELAY_OWNER_NPUB' => $owner_key(Key::public(\nostriphant\Transpher\Nostr\Key\Format::BECH32)),
        'RELAY_NAME' => 'Really relay',
        'RELAY_DESCRIPTION' => 'This is my dev relay',
        'RELAY_CONTACT' => 'nostr@rikmeijer.nl'
    ]);

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

    $relay();
});
