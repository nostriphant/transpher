<?php

use nostriphant\Transpher\Nostr\Message\Factory;
use function Pest\incoming;

it('downloads NIP-92 files (kind 1, with imeta tag) into a data folder', function () {
    $file = tempnam(sys_get_temp_dir(), 'file');
    file_put_contents($file, uniqid());
    $hash = hash_file('sha256', $file);

    expect(ROOT_DIR . '/data/files/' . $hash)->not()->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events')->not()->toBeDirectory();

    $sender_key = \Pest\key_sender();
    $message = Factory::event($sender_key, 1, 'Note with a reference to file://' . $file,
            ['imeta',
                'url file://' . $file,
                'm text/plain',
                'x ' . $hash
            ]
    );

    expect(\Pest\handle($message, incoming()))->toHaveReceived(
            ['OK', $message()[1]['id'], true]
    );

    expect(ROOT_DIR . '/data/files/' . $hash)->toBeFile();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events')->toBeDirectory();
    expect(ROOT_DIR . '/data/files/' . $hash . '.events/' . $message()[1]['id'])->toBeFile();
});