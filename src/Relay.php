<?php

namespace nostriphant\Transpher;

use nostriphant\Stores\Store;

class Relay {
    public function __construct(Store $events, string $files_path) {
        $this->server = new \nostriphant\Transpher\Amp\WebsocketServer($events, $files_path);
    }
    
    public function __invoke(string $ip, string $port, int $max_connections_per_ip, \Psr\Log\LoggerInterface $log, callable $shutdown_callback): void {
        ($this->server)($ip, $port, $max_connections_per_ip, $log, $shutdown_callback);
    }
}
