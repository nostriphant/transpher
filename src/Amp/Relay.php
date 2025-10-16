<?php

namespace nostriphant\Transpher\Amp;

use \Psr\Log\LoggerInterface;
use nostriphant\Transpher\Amp\WebsocketClientHandler;
use Amp\Websocket\Server\WebsocketClientGateway;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use Amp\Websocket\Server\Websocket;
use Amp\Http\Server\ErrorHandler;
use Amp\Websocket\Server\Rfc6455Acceptor;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;

use nostriphant\Transpher\Files;
use nostriphant\Transpher\Relay\Incoming;
use nostriphant\Stores\Store;
use nostriphant\Transpher\Relay\Blossom;
use nostriphant\Functional\Await;

class Relay {
    private ErrorHandler $errorHandler;
    private Files $files;

    public function __construct(Store $events, string $files_path) {
        $this->files = new Files($files_path, $events);
        $this->errorHandler = new DefaultErrorHandler();
        $this->clientHandler = new WebsocketClientHandler(new Incoming($events, $this->files), new WebsocketClientGateway());   
    }

    public function __invoke(string $ip, string $port, int $max_connections_per_ip, LoggerInterface $log, callable $shutdown_callback): void {
        $server = SocketHttpServer::createForDirectAccess($log, connectionLimitPerIp: $max_connections_per_ip);
        $server->expose(new Socket\InternetAddress($ip, $port));

        $router = new Router($server, $log, $this->errorHandler);
        $acceptor = new Rfc6455Acceptor();
        //$acceptor = new AllowOriginAcceptor(
        //    ['http://localhost:' . $port, 'http://127.0.0.1:' . $port, 'http://[::1]:' . $port],
        //);
        $router->addRoute('GET', '/', new RequestHandler(new Websocket($server, $log, $acceptor, $this->clientHandler)));

        $blossom = new Blossom($this->files);
        $blossom_handler = new ClosureRequestHandler(fn(Request $request) => new Response(...$blossom(...$request->getAttribute(Router::class))));
        $routes = Blossom::ROUTES;
        array_walk($routes, fn(string $route, string $method) => $router->addRoute($method, $route, $blossom_handler));

        $server->start($router, $this->errorHandler);

        (new Await(fn() => \Amp\trapSignal([SIGINT, SIGTERM])))(function(int $signal) use ($shutdown_callback, $server) {
            $shutdown_callback($signal);
            $server->stop();
        });
    }

}
