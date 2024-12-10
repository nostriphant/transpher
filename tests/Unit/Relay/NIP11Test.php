<?php

use nostriphant\Transpher\Relay\InformationDocument;

it('generates Relay Information Document', function () {

    $name = 'Transpher Relay';
    $description = 'Some interesting description goes here';
    $owner_pubkey = 'c0bb181bc39c4e59768805bbc5bdd34c508f14b01a298d63be4510d97417ce01';
    $contact = 'transpher@nostriphant.dev';

    expect(InformationDocument::generate($name, $description, $owner_pubkey, $contact))->toBe([
        "name" => 'Transpher Relay',
        "description" => 'Some interesting description goes here',
        "pubkey" => 'c0bb181bc39c4e59768805bbc5bdd34c508f14b01a298d63be4510d97417ce01',
        "contact" => 'transpher@nostriphant.dev',
        "supported_nips" => [1, 2, 9, 11, 12, 13, 16, 20, 22, 33, 45, 92, 94],
        "software" => 'Transpher',
        "version" => 'dev'
    ]);
});
