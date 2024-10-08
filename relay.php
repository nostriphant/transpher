<?php

require_once __DIR__ . '/bootstrap.php';

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Server\WebsocketClientGateway;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use function Amp\trapSignal;

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
$clientHandler = new \Transpher\WebSocket\ClientHandler($relay, $logger, new WebsocketClientGateway());

$websocket = new Transpher\WebSocket\RequestHandler(new Websocket($server, $logger, $acceptor, $clientHandler));

$router = new Router($server, $logger, $errorHandler);
$router->addRoute('GET', '/', $websocket);

$server->start($router, $errorHandler);

// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$logger->info(sprintf("Received signal %d, stopping Relay server", $signal));

$server->stop();