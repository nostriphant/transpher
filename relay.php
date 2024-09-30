<?php

require_once __DIR__ . '/bootstrap.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

use Functional\Functional;
use function \Functional\first, \Functional\reject, \Functional\if_else, \Functional\map;

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
    
    public function wrapOthers(array $others) {
        return map($others, fn(\WebSocket\Connection $client) => fn(array $event) => first(
            $client->getMeta('subscriptions')??[], 
            fn(callable $subscription, string $subscriptionId) => if_else(
                $subscription, 
                fn(array $event) => $this->wrapClient($client, 'Relay')(
                    \Transpher\Nostr\Message::requestedEvent($subscriptionId, $event),
                    \Transpher\Nostr\Message::eose($subscriptionId)
                ),
                Functional::false
            )($event)
        ));
    }
    
    private function wrapClient(\WebSocket\Connection $client, string $action) : callable {
        return function(array ...$messages) use ($client, $action) : bool {
            foreach ($messages as $message) {
                $encoded_message = \Transpher\Nostr::encode($message);
                $this->log->debug($action . ' message ' . $encoded_message);
                $client->text($encoded_message);
            }
            return true;
        };
    }
    
    public function onJson(callable $callback) {
        $this->onText(function (\WebSocket\Server $server, \WebSocket\Connection $from, \WebSocket\Message\Message $message) use ($callback) {
            $this->log->info('Received message: ' . $message->getPayload());
            $payload = \Transpher\Nostr::decode($message->getPayload());
            
            $others = $this->wrapOthers(reject($this->getConnections(), fn(\WebSocket\Connection $client) => $client === $from));
            $subscriptions = function(?array $subscriptions = null) use ($from) : array {
                if (isset($subscriptions)) {
                    $from->setMeta('subscriptions', $subscriptions);
                }
                return $from->getMeta('subscriptions')??[];
            };
            
            $reply = $this->wrapClient($from, 'Reply');
            foreach($callback($subscriptions, $others, $payload) as $reply_message) {
                $reply($reply_message);
            }
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

$relay = new \Transpher\Nostr\Relay($events);
$websocket->onJson($relay);
$websocket->start();