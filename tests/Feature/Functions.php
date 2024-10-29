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
}
