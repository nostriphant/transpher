<?php

namespace nostriphant\Transpher\Amp;

use \Psr\Log\LoggerInterface;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\Server\WebsocketClientGateway;
use Amp\Websocket\WebsocketClient;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use nostriphant\NIP01\Message;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use Amp\Websocket\Server\Websocket;
use Amp\Http\Server\ErrorHandler;
use Amp\Websocket\Server\Rfc6455Acceptor;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;

use nostriphant\Transpher\Relay\Blossom;

readonly class Relay implements WebsocketClientHandler {

    private WebsocketGateway $gateway;
    private ErrorHandler $errorHandler;

    public function __construct(private \nostriphant\Transpher\Relay\Context $context) {
        $this->gateway = new WebsocketClientGateway();
        $this->errorHandler = new DefaultErrorHandler();
    }


    public function __invoke(string $ip, string $port, int $max_connections_per_ip, LoggerInterface $log): AwaitSignal {
        $server = SocketHttpServer::createForDirectAccess($log, connectionLimitPerIp: $max_connections_per_ip);
        $server->expose(new Socket\InternetAddress($ip, $port));

        $router = new Router($server, $log, $this->errorHandler);
        $acceptor = new Rfc6455Acceptor();
        //$acceptor = new AllowOriginAcceptor(
        //    ['http://localhost:' . $port, 'http://127.0.0.1:' . $port, 'http://[::1]:' . $port],
        //);
        $router->addRoute('GET', '/', new RequestHandler(new Websocket($server, $log, $acceptor, $this)));

        $blossom = new Blossom($this->context->files);
        $blossom_handler = new ClosureRequestHandler(fn(Request $request) => new Response(...$blossom(...$request->getAttribute(Router::class))));
        $routes = Blossom::ROUTES;
        array_walk($routes, fn(string $route, string $method) => $router->addRoute($method, $route, $blossom_handler));

        $server->start($router, $this->errorHandler);

        return new AwaitSignal(fn() => $server->stop());
    }

    #[\Override]
    public function handleClient(
            WebsocketClient $client,
            Request $request,
            Response $response,
    ): void {

        $this->gateway->addClient($client);

        $wrapped_client = SendNostr::send($client);
        $incoming = call_user_func($this->context, $wrapped_client);
        foreach ($client as $message) {
            try {
                foreach ($incoming(Message::decode((string) $message)) as $reply) {
                    $wrapped_client($reply);
                }
            } catch (\InvalidArgumentException $ex) {
                $wrapped_client(Message::notice($ex->getMessage()));
            }
        }
    }
}
