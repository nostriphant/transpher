<?php

require_once __DIR__ . '/bootstrap.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use function \Functional\reject, \Functional\map;

Transpher\Process::gracefulExit();

$port = $_SERVER['argv'][1] ?? 80;
            
// create a log channel
$log = new Logger('relay-' . $port);
$log->pushHandler(new StreamHandler(__DIR__ . '/logs/server.log', Level::Debug));
$log->pushHandler(new StreamHandler(STDOUT), Level::Info);

$websocket = new class($port, $log) extends WebSocket\Server {
    
    public function __construct(int $port, private \Psr\Log\LoggerInterface $log) {
        parent::__construct($port);
        $this
            ->addMiddleware(new \WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new \WebSocket\Middleware\PingResponder())
            ->setLogger($log);
        
    }
    
    public function getOthers(\WebSocket\Connection $from, callable $wrap) {
        $others = reject($this->getConnections(), fn(\WebSocket\Connection $client) => $client === $from); 
        return map($others, $wrap);
    }
    
    public function onJson(callable $callback) {
        $this->onText(function (\WebSocket\Server $server, \WebSocket\Connection $from, \WebSocket\Message\Message $message) use ($callback) {
            $this->log->info('Received message: ' . $message->getPayload());
            $payload = \Transpher\Nostr::decode($message->getPayload());
            
            $callback($from, $payload);
        });
    }
};

        
if (isset($_SERVER['TRANSPHER_STORE']) === false) {
    $log->info('Using memory to save messages.');
    $events = [];
} elseif (str_starts_with($_SERVER['TRANSPHER_STORE'], 'redis')) {
    $log->info('Using redis to store messages');
    $events = new Transpher\Redis($_SERVER['TRANSPHER_STORE']);
} elseif (is_dir($_SERVER['TRANSPHER_STORE'])) {
    $log->info('Using directory to store messages');
    $events = new Transpher\Directory($_SERVER['TRANSPHER_STORE']);
} else {
    $log->info('Using memory to save messages (fallback).');
    $events = [];
}

$server = new \Transpher\WebSocket\Server($websocket, $log, $events);
$server->start();