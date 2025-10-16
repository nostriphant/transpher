<?php

namespace nostriphant\Transpher;

use nostriphant\Stores\Store;

class Relay {
    public function __construct(Store $events, string $files_path) {
        $this->server = new \nostriphant\Transpher\Amp\WebsocketServer($events, $files_path);
    }
    
    public function __invoke(string $ip, string $port, int $max_connections_per_ip, \Psr\Log\LoggerInterface $log): void {
        ($this->server)($ip, $port, $max_connections_per_ip, $log, fn(int $signal) => $log->info(sprintf("Received signal %d, stopping Relay server", $signal)));
    }
}
