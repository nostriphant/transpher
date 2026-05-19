<?php

namespace nostriphant\RelayTests\Feature;

use nostriphant\NIP01\Key;
use nostriphant\TranspherTests\Transpher;

it('boots a relay instance, which responds with an NIP-11 information document on a "GET /" request', function() {
    $recipient = Key::fromHex('6eeb5ad99e47115467d096e07c1c9b8b41768ab53465703f78017204adc5b0cc');
    
    $transpher = new Transpher('8092', $recipient, []);
    expect($transpher->url)->toStartWith('http://');
    
    list($protocol, $status, $headers, $body) = \nostriphant\HTTP\request('GET', $transpher->url . '/', headers: ['Accept: application/nostr+json']);
    expect($status)->toBe('200');
    //$body = $this->expectRelayResponse('/', 200, 'application/nostr+json', headers:['Accept: application/nostr+json']);
    expect($body)->toBe(json_encode([
            'name' => 'Really relay',
            'description' => 'This is my dev relay',
            'pubkey' => '2b0d6f7a9c30264fed56ab9759761a47ce155bb04eea5ab47ab00dc4b9cb61c0',
            'contact' =>'transpher@nostriphant.dev',
            'supported_nips' => [1, 2, 9, 11, 12, 13, 16, 20, 22, 33, 45],
            'software' => "https://github.com/nostriphant/transpher",
            'version' => TRANSPHER_VERSION
    ]));
    
    $transpher();
});

it('reproduce nostria 504 error on information document request', function() {
    $recipient = Key::fromHex('6eeb5ad99e47115467d096e07c1c9b8b41768ab53465703f78017204adc5b0cc');

    $transpher = new Transpher('8093', $recipient, []);
    
    list($protocol, $status, $headers, $body) = \nostriphant\HTTP\request('GET', $transpher->url, headers: [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0',
        'Accept: application/nostr+json',
        'Accept-Language: nl,en;q=0.9,en-US;q=0.8',
        'Accept-Encoding: gzip, deflate, br, zstd',
        'Referer: https://nostria.app/',
        'Origin: https://nostria.app',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: cross-site',
        'Connection: keep-alive'
    ]);
    expect($status)->toBe('200');
    
    
    $transpher();
});