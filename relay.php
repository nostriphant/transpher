<?php

require_once __DIR__ . '/bootstrap.php';

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Amp\Socket;
use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Server\WebsocketClientGateway;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\WebsocketClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use function Amp\trapSignal;
use Functional\Functional;
use function \Functional\first, \Functional\reject, \Functional\if_else, \Functional\map;

$port = $_SERVER['argv'][1] ?? 80;

$logger = new Logger('relay-' . $port);
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/server.log', Level::Debug));
$logger->pushHandler(new StreamHandler(STDOUT, Level::Info));

$server = SocketHttpServer::createForDirectAccess($logger);

$server->expose(new Socket\InternetAddress('127.0.0.1', $port));
$server->expose(new Socket\InternetAddress('[::1]', $port));

$errorHandler = new DefaultErrorHandler();

$acceptor = new Amp\Websocket\Server\Rfc6455Acceptor();
//$acceptor = new AllowOriginAcceptor(
//    ['http://localhost:' . $port, 'http://127.0.0.1:' . $port, 'http://[::1]:' . $port],
//);


if (isset($_SERVER['TRANSPHER_STORE']) === false) {
    $logger->info('Using memory to save messages.');
    $events = [];
} elseif (str_starts_with($_SERVER['TRANSPHER_STORE'], 'redis')) {
    $logger->info('Using redis to store messages');
    $events = new Transpher\Redis($_SERVER['TRANSPHER_STORE']);
} elseif (is_dir($_SERVER['TRANSPHER_STORE'])) {
    $logger->info('Using directory to store messages');
    $events = new Transpher\Directory($_SERVER['TRANSPHER_STORE']);
} else {
    $logger->info('Using memory to save messages (fallback).');
    $events = [];
}

$relay = new \Transpher\Nostr\Relay($events);
$clientHandler = new class($relay, $logger) implements WebsocketClientHandler {
    
    private array $subscriptions = [];
    
    public function __construct(
        private readonly \Transpher\Nostr\Relay $relay,
        private readonly \Psr\Log\LoggerInterface $log,
        private readonly WebsocketGateway $gateway = new WebsocketClientGateway()
    ) {
    }
    
    public function wrapOthers(array $others) {
        return map($others, fn(WebsocketClient $client) => fn(array $event) => first(
            $this->subscriptions($client->getId()), 
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

    private function wrapClient(WebsocketClient $client, string $action) : callable {
        return function(array ...$messages) use ($client, $action) : bool {
            foreach ($messages as $message) {
                $encoded_message = \Transpher\Nostr::encode($message);
                $this->log->debug($action . ' message ' . $encoded_message);
                $client->sendText($encoded_message);
            }
            return true;
        };
    }
    
    private function subscriptions(string $clientId, ?array $subscriptions = null) : array {
        if (isset($subscriptions)) {
            $this->subscriptions[$clientId] = $subscriptions;
        }
        return $this->subscriptions[$clientId]??[];
    }
    
    #[\Override]
    public function handleClient(
        WebsocketClient $client,
        Request $request,
        Response $response,
    ): void {
        $others = $this->wrapOthers($this->gateway->getClients());
        $this->gateway->addClient($client);

        /* \Amp\Websocket\WebsocketMessage $message */
        foreach ($client as $message) {
            $payload = (string)$message;
            $this->log->info('Received message: ' . $payload);
            $payload = \Transpher\Nostr::decode($payload);

            $subscriptions = fn(?array $subscriptions = null) => $this->subscriptions($client->getId(), $subscriptions);

            $reply = $this->wrapClient($client, 'Reply');
            foreach(($this->relay)($subscriptions, $others, $payload) as $reply_message) {
                $reply($reply_message);
            }
        }
    }
};

$websocket = new Websocket($server, $logger, $acceptor, $clientHandler);

$router = new Router($server, $logger, $errorHandler);
$router->addRoute('GET', '/', $websocket);
$router->setFallback(new DocumentRoot($server, $errorHandler, ROOT_DIR . '/public'));

$server->start($router, $errorHandler);

// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$logger->info(sprintf("Received signal %d, stopping HTTP server", $signal));

$server->stop();