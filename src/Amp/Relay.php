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

use nostriphant\Transpher\Files;
use nostriphant\Transpher\Relay\Incoming;
use nostriphant\Stores\Store;
use nostriphant\Transpher\Relay\Blossom;
use nostriphant\Transpher\Relay\Subscriptions;

readonly class Relay implements WebsocketClientHandler {

    private WebsocketGateway $gateway;
    private ErrorHandler $errorHandler;
    private Files $files;

    public function __construct(private Store $events, string $files_path, readonly bool $authentication = false) {
        $this->files = new Files($files_path, $events);
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

        $blossom = new Blossom($this->files);
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
        $client_context = new Incoming\Context($this->events, $this->files, new Subscriptions($wrapped_client));

        $enabled_types = [
            new Incoming\Event(new Incoming\Event\Accepted($client_context), Incoming\Event\Limits::fromEnv()),
            new Incoming\Close($client_context),
            new Incoming\Req(new Incoming\Req\Accepted($client_context, Incoming\Req\Accepted\Limits::fromEnv()), Incoming\Req\Limits::fromEnv()),
            new Incoming\Count($client_context, Incoming\Count\Limits::fromEnv())
        ];

        if ($this->authentication) {
            $wrapped_client(Message::auth(bin2hex(random_bytes(32))));
            $enabled_types[] = new Incoming\Auth(Incoming\Auth\Limits::fromEnv());
        }

        $incoming = new Incoming(...$enabled_types);

        foreach ($client as $message) {
            $payload = (string) $message;
            try {
                foreach ($incoming(Message::decode($payload)) as $reply) {
                    $wrapped_client($reply);
                }
            } catch (\InvalidArgumentException $ex) {
                $wrapped_client(Message::notice($ex->getMessage()));
            }
        }
    }
}
