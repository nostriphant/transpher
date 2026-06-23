<?php

namespace nostriphant\TranspherTests;

class Relay {
    private Feature\Process $process;
    
    public function __construct(public string $socket, public array $env) {
        $cmd = [PHP_BINARY, ROOT_DIR . DIRECTORY_SEPARATOR . 'relay.php', $socket];
        list($scheme, $uri) = explode(":", $socket, 2);
        
        $port = parse_url($socket, PHP_URL_PORT) ?? '80';
        
        $this->process = new Feature\Process('relay-' . $port, $cmd, $env, \nostriphant\Functional\Partial::right('str_contains', 'Listening on http:' . $uri . '/'));
    }
    
    public function __invoke(): mixed {
        return call_user_func($this->process);
    }
}
