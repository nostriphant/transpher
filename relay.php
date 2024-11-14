<?php

require_once __DIR__ . '/bootstrap.php';

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use Amp\Websocket\Server\Websocket;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use function Amp\trapSignal;
use nostriphant\Transpher\RequestHandler;

list($ip, $port) = explode(":", $_SERVER['argv'][1]);

$logger = new Logger('relay-' . $port);
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/server.log', Level::Debug));
$logger->pushHandler(new StreamHandler(STDOUT, Level::Info));

$server = SocketHttpServer::createForDirectAccess($logger, connectionLimitPerIp: $_SERVER['RELAY_MAX_CONNECTIONS_PER_IP'] ?? 1000);
$server->expose(new Socket\InternetAddress($ip, $port));

$errorHandler = new DefaultErrorHandler();

$acceptor = new Amp\Websocket\Server\Rfc6455Acceptor();
//$acceptor = new AllowOriginAcceptor(
//    ['http://localhost:' . $port, 'http://127.0.0.1:' . $port, 'http://[::1]:' . $port],
//);

$store_path = $_SERVER['RELAY_STORE'] ?? ROOT_DIR . '/data/events';
is_dir($store_path) || mkdir($store_path);
$events = new nostriphant\Transpher\Directory($store_path);

$files_path = $_SERVER['RELAY_FILES'] ?? ROOT_DIR . '/data/files';
is_dir($files_path) || mkdir($files_path);
$files = new \nostriphant\Transpher\Files($files_path);

$incoming = new \nostriphant\Transpher\Relay\Incoming($events, $files);
$clientHandler = new \nostriphant\Transpher\Relay($incoming, $logger);

$router = new Router($server, $logger, $errorHandler);
$router->addRoute('GET', '/', new RequestHandler(new Websocket($server, $logger, $acceptor, $clientHandler)));
$router->addRoute('GET', '/{file:\w+}', new class($files) implements \Amp\Http\Server\RequestHandler {

    public function __construct(private \nostriphant\Transpher\Files $files) {
        
    }


    #[\Override]
    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Http\Server\Response {
        if (strcasecmp($request->getMethod(), 'HEAD') === 0) {
            return new \Amp\Http\Server\Response(headers: ['Content-Type' => 'text/plain'], body: '');
        } else {
            $args = $request->getAttribute(Router::class);
            return new \Amp\Http\Server\Response(
                    headers: ['Content-Type' => 'text/plain'],
                    body: ($this->files)($args['file'])()
            );
        }
    }
});

$server->start($router, $errorHandler);

// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$logger->info(sprintf("Received signal %d, stopping Relay server", $signal));

$server->stop();