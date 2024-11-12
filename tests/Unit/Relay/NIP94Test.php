<?php


use nostriphant\Transpher\Nostr\Message\Factory;
use function Pest\incoming;

it('downloads NIP-94 files (kind 1063) into a data folder', function () {
    $file = tempnam(sys_get_temp_dir(), 'file');
    file_put_contents($file, 'Hello world!');
    $hash = hash_file('sha256', $file);

    unlink(ROOT_DIR . '/data/files/' . $hash);
    expect(ROOT_DIR . '/data/files/' . $hash)->not()->toBeFile();

    $sender_key = \Pest\key_sender();
    $message = Factory::event($sender_key, 1063, 'File caption with the description of its contents',
            ['url', 'file://' . $file],
            ['m', 'text/plain'],
            ['x', hash_file('sha256', $file)],
            ['ox', hash_file('sha256', $file)]
    );

    expect(\Pest\handle($message, incoming()))->toHaveReceived(
            ['OK']
    );

    expect(ROOT_DIR . '/data/files/' . $hash)->toBeFile();
});
