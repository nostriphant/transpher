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
use rikmeijer\Transpher\WebSocket\RequestHandler;

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

$store_path = ROOT_DIR . '/data';
is_dir($store_path) || mkdir($store_path);
$events = new rikmeijer\Transpher\Directory($store_path);

$relay = new \rikmeijer\Transpher\Nostr\Relay($events);
$clientHandler = new \rikmeijer\Transpher\WebSocket\ClientHandler($relay, $logger, new WebsocketClientGateway());

$router = new Router($server, $logger, $errorHandler);
$router->addRoute('GET', '/', new RequestHandler(new Websocket($server, $logger, $acceptor, $clientHandler)));

$server->start($router, $errorHandler);

// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$logger->info(sprintf("Received signal %d, stopping Relay server", $signal));

$server->stop();