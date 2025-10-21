<?php

namespace nostriphant\TranspherTests\Feature;

class Functions {

    static function bootAgent(int $port, array $env): Process {
        $cmd = [PHP_BINARY, ROOT_DIR . DIRECTORY_SEPARATOR . 'agent.php', $port];
        return new Process('agent-' . $port, $cmd, $env, fn(string $line) => str_contains($line, 'Client connecting to ws://127.0.0.1'));
    }

    static function bootRelay(string $address, array $env): Process {
        $cmd = [PHP_BINARY, ROOT_DIR . DIRECTORY_SEPARATOR . 'relay.php', $address];
        list($ip, $port) = explode(':', $address);
        return new Process('relay-' . $port, $cmd, $env, fn(string $line) => str_contains($line, 'Listening on http://127.0.0.1:' . $port . '/'));
    }
    
    static function unwrapper(\nostriphant\NIP01\Key $recipient_key) {
        return function(array $gift) use ($recipient_key) {
            expect($gift['kind'])->toBe(1059);
            expect($gift['tags'])->toContain(['p', $recipient_key(\nostriphant\NIP01\Key::public())]);

            $seal = Gift::unwrap($recipient_key, Event::__set_state($gift));
            expect($seal->kind)->toBe(13);
            expect($seal->pubkey)->toBeString();
            expect($seal->content)->toBeString();

            $private_message = Seal::open($recipient_key, $seal);
            expect($private_message)->toHaveKey('id');
            expect($private_message)->toHaveKey('content');
            return $private_message['content'];
        };
    }
}
