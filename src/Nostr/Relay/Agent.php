<?php

namespace Transpher\Nostr\Relay;

/**
 * Description of Agent
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Agent {
    static function boot(int $port, array $env, callable $running) {
        $cmd = [PHP_BINARY, ROOT_DIR . DIRECTORY_SEPARATOR . 'agent.php', $port];
        \Transpher\Process::start('agent-' . $port, $cmd, $env, fn(string $line) => str_contains($line, 'Client connected to ws://127.0.0.1:' . $port), $running);
    }
}
