<?php

require_once __DIR__ . '/bootstrap.php';

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Http\HttpStatus;
use Amp\Socket;
use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Server\WebsocketClientGateway;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\WebsocketClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use function Amp\trapSignal, \Functional\each;
use Transpher\Nostr\Relay\Subscriptions;

list($ip, $port) = explode(":", $_SERVER['argv'][1]);

$logger = new Logger('relay-' . $port);
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/server.log', Level::Debug));
$logger->pushHandler(new StreamHandler(STDOUT, Level::Info));

$server = SocketHttpServer::createForDirectAccess($logger);
$server->expose(new Socket\InternetAddress($ip, $port));

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
    
    public function __construct(
        private readonly \Transpher\Nostr\Relay $relay,
        private readonly \Psr\Log\LoggerInterface $log,
        private readonly WebsocketGateway $gateway = new WebsocketClientGateway()
    ) {
    }
    
    #[\Override]
    public function handleClient(
        WebsocketClient $client,
        Request $request,
        Response $response,
    ): void {
        $this->gateway->addClient($client);
        foreach ($client as $message) {
            $payload = (string)$message;
            $this->log->info('Received message: ' . $payload);
            $relay = new Transpher\WebSocket\SendNostr('Relay', $client, $this->log);
            each(($this->relay)($payload, Subscriptions::makeStore(), $relay), new Transpher\WebSocket\SendNostr('Reply', $client, $this->log));
        }
    }
};

$websocket = new class(new WebSocket($server, $logger, $acceptor, $clientHandler)) implements RequestHandler {
    
    public function __construct(private WebSocket $websocket) {
    }
    public function __call(string $name, array $arguments): mixed {
        return $this->websocket->$name(...$arguments);
    }
    
    #[\Override]
    public function handleRequest(Request $request): Response {
        $response =  $this->websocket->handleRequest($request);
        if ($response->getStatus() === HttpStatus::UPGRADE_REQUIRED) {
            return new Response(
                headers: ['Content-Type' => 'application/json'],
                body: json_encode(\Transpher\Nostr\Relay\InformationDocument::generate(
                    $_SERVER['RELAY_NAME'],
                    $_SERVER['RELAY_DESCRIPTION'],
                    $_SERVER['RELAY_OWNER_NPUB'],
                    $_SERVER['RELAY_CONTACT']
                ))
            );
        }
        
        return $response;
    }
};

$router = new Router($server, $logger, $errorHandler);
$router->addRoute('GET', '/', $websocket);

$server->start($router, $errorHandler);

// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$logger->info(sprintf("Received signal %d, stopping Relay server", $signal));

$server->stop();