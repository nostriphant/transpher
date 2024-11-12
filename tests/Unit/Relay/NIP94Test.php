<?php


use nostriphant\Transpher\Nostr\Message\Factory;
use function Pest\incoming;

it('downloads NIP-94 files (kind 1063) into a data folder', function () {
    $file = tempnam(sys_get_temp_dir(), 'file');
    file_put_contents($file, 'Hello world 2!');
    $hash = hash_file('sha256', $file);

    unlink(ROOT_DIR . '/data/files/' . $hash);
    expect(ROOT_DIR . '/data/files/' . $hash)->not()->toBeFile();

    $sender_key = \Pest\key_sender();
    $message = Factory::event($sender_key, 1063, 'File caption with the description of its contents',
            ['url', 'file://' . $file],
            ['m', 'text/plain'],
            ['x', $hash],
            ['ox', $hash]
    );

    expect(\Pest\handle($message, incoming()))->toHaveReceived(
            ['OK', $message()[1]['id'], true]
    );

    expect(ROOT_DIR . '/data/files/' . $hash)->toBeFile();
});


it('refuses NIP-94 files with missing url-tag', function () {
    $hash = hash('sha256', 'Hello world 2!');

    $sender_key = \Pest\key_sender();
    $message = Factory::event($sender_key, 1063, 'File caption with the description of its contents',
            ['m', 'text/plain'],
            ['x', $hash],
            ['ox', $hash]
    );

    expect(\Pest\handle($message, incoming()))->toHaveReceived(
            ['OK', $message()[1]['id'], false, 'invalid:missing url-tag']
    );

});
