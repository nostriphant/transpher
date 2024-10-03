<?php

namespace Transpher\Nostr\Relay;
use \Transpher\Process;

/**
 * Description of Agent
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Agent {
    static function boot(int $port, array $env) : Process {
        $cmd = [PHP_BINARY, ROOT_DIR . DIRECTORY_SEPARATOR . 'agent.php', $port];
        return new Process('agent-' . $port, $cmd, $env, fn(string $line) => str_contains($line, 'Client connecting to ws://127.0.0.1'));
    }
}
