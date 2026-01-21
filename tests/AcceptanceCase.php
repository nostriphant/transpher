<?php

namespace nostriphant\TranspherTests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use nostriphant\TranspherTests\Feature\Process;


abstract class AcceptanceCase extends BaseTestCase
{
    static function bootAgent(int $port, array $env): Process {
        $cmd = [PHP_BINARY, ROOT_DIR . DIRECTORY_SEPARATOR . 'agent.php', $port];
        return new Process('agent-' . $port, $cmd, $env, fn(string $line) => str_contains($line, 'Listening to relay...'));
    }

    static function bootRelay(string $socket, array $env): Process {
        $cmd = [PHP_BINARY, ROOT_DIR . DIRECTORY_SEPARATOR . 'relay.php', $socket];
        list($scheme, $uri) = explode(":", $socket, 2);
        return new Process('relay-' . substr(sha1($socket), 0, 6), $cmd, $env, fn(string $line) => str_contains($line, 'Listening on http:' . $uri . '/'));
    }
    
    static public function unwrap(\nostriphant\NIP01\Key $recipient_key) {
        return function(array $gift) use ($recipient_key) {
            expect($gift['kind'])->toBe(1059);
            expect($gift['tags'])->toContain(['p', $recipient_key(\nostriphant\NIP01\Key::public())]);

            $seal = \nostriphant\NIP59\Gift::unwrap($recipient_key, \nostriphant\NIP01\Event::__set_state($gift));
            expect($seal->kind)->toBe(13);
            expect($seal->pubkey)->toBeString();
            expect($seal->content)->toBeString();

            $private_message = \nostriphant\NIP59\Seal::open($recipient_key, $seal);
            expect($private_message)->toHaveKey('id');
            expect($private_message)->toHaveKey('content');
            return $private_message->content;
        };
    }
    
    static public function relay_url(string $scheme = 'ws://', string $port = '8087') {
        return $scheme . '127.0.0.1:' . $port;
    }

    static function data_dir(?string $relay_id = null) {
        $data_dir = ROOT_DIR . '/data/relay_' . ($relay_id ?? uniqid('', true));
        is_dir($data_dir) || mkdir($data_dir);
        return $data_dir;
    }
}
