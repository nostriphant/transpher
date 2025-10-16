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
use Amp\Websocket\Server\Rfc6455Acceptor;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;

use nostriphant\Functional\Await;

readonly class WebsocketServer {
    
    private \Amp\Websocket\Server\WebsocketClientHandler $clientHandler;
    
    public function __construct(MessageHandlerFactory $messageHandlerFactory, private \Closure $static_routes) {
        $this->clientHandler = new WebsocketClientHandler($messageHandlerFactory, new WebsocketClientGateway());   
    }

    public function __invoke(string $ip, string $port, int $max_connections_per_ip, LoggerInterface $log, callable $shutdown_callback): void {
        $errorHandler = new DefaultErrorHandler();
        
        $server = SocketHttpServer::createForDirectAccess($log, connectionLimitPerIp: $max_connections_per_ip);
        $server->expose(new Socket\InternetAddress($ip, $port));

        $router = new Router($server, $log, $errorHandler);
        $acceptor = new Rfc6455Acceptor();
        //$acceptor = new AllowOriginAcceptor(
        //    ['http://localhost:' . $port, 'http://127.0.0.1:' . $port, 'http://[::1]:' . $port],
        //);
        $router->addRoute('GET', '/', new RequestHandler(new Websocket($server, $log, $acceptor, $this->clientHandler)));

        ($this->static_routes)(fn(string $method, string $route, callable $endpoint) => $router->addRoute($method, $route, new ClosureRequestHandler(fn(Request $request) => new Response(...$endpoint(...$request->getAttribute(Router::class))))));
        
        $server->start($router, $errorHandler);

        (new Await(fn() => \Amp\trapSignal([SIGINT, SIGTERM])))(function(int $signal) use ($shutdown_callback, $server) {
            $shutdown_callback($signal);
            $server->stop();
        });
    }

}
