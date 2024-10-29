<?php

use nostriphant\Transpher\Nostr\Key;
use nostriphant\TranspherTests\Feature\Functions;
use nostriphant\Transpher\Relay\InformationDocument;

describe('relay', function () {
    it('sends an information document (NIP-11), when on a HTTP request', function() {
        $owner_key = \Pest\key_sender();;
        $agent_key = \Pest\key_recipient();

        $relay = Functions::bootRelay('127.0.0.1:8087', $env = [
            'AGENT_NSEC' => $agent_key(Key::private(\nostriphant\Transpher\Nostr\Key\Format::BECH32)),
            'RELAY_URL' => 'ws://127.0.0.1:8087',
            'RELAY_OWNER_NPUB' => $owner_key(Key::public(\nostriphant\Transpher\Nostr\Key\Format::BECH32)), 
            'RELAY_NAME' => 'Really relay',
            'RELAY_DESCRIPTION' => 'This is my dev relay',
            'RELAY_CONTACT' => 'nostr@rikmeijer.nl'
        ]);


        $curl = curl_init('http://localhost:8087');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept: application/nostr+json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $responseText = curl_exec($curl);
        expect($responseText)->not()->toBeFalse('['. curl_errno($curl).'] ' . curl_error($curl));
        expect($responseText)->not()->toContain('<b>Warning</b>');
        $response = \nostriphant\Transpher\Nostr::decode($responseText);

        expect($response)->not()->toBeNull($responseText);
        expect($response)->toBe(InformationDocument::generate($env['RELAY_NAME'], $env['RELAY_DESCRIPTION'], $env['RELAY_OWNER_NPUB'], $env['RELAY_CONTACT']));

        $relay();
    });
});
