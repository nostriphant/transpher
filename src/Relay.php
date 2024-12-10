<?php

namespace nostriphant\Transpher;


use function \Functional\each;
use \Psr\Log\LoggerInterface;
use \nostriphant\Transpher\Relay;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\Server\WebsocketClientGateway;
use Amp\Websocket\WebsocketClient;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\SendNostr;
use nostriphant\NIP01\Message;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use Amp\Websocket\Server\Websocket;
use function Amp\trapSignal;
use nostriphant\Transpher\RequestHandler;
use Amp\Http\Server\ErrorHandler;
use Amp\Websocket\Server\Rfc6455Acceptor;

class Relay implements WebsocketClientHandler {

    private array $subscriptions = [];
    private WebsocketGateway $gateway;
    private Relay\Incoming $incoming;
    private ErrorHandler $errorHandler;
    private Files $files;

    public function __construct(private Relay\Store $events, string $files_path, private LoggerInterface $log) {
        $this->files = new \nostriphant\Transpher\Files($files_path, $events);
        $this->incoming = new \nostriphant\Transpher\Relay\Incoming($events, $this->files);
        $this->gateway = new WebsocketClientGateway();
        $this->errorHandler = new DefaultErrorHandler();
    }


    public function __invoke(string $ip, string $port, int $max_connections_per_ip): void {
        $server = SocketHttpServer::createForDirectAccess($this->log, connectionLimitPerIp: $max_connections_per_ip);
        $server->expose(new Socket\InternetAddress($ip, $port));

        $router = new Router($server, $this->log, $this->errorHandler);
        $acceptor = new Rfc6455Acceptor();
        //$acceptor = new AllowOriginAcceptor(
        //    ['http://localhost:' . $port, 'http://127.0.0.1:' . $port, 'http://[::1]:' . $port],
        //);
        $router->addRoute('GET', '/', new RequestHandler(new Websocket($server, $this->log, $acceptor, $this)));
        Relay\Blossom::connect($this->files, $router);

        $server->start($router, $this->errorHandler);

        // Await SIGINT or SIGTERM to be received.
        $signal = trapSignal([SIGINT, SIGTERM]);

        $this->log->info(sprintf("Received signal %d, stopping Relay server", $signal));

        $server->stop();
    }

    #[\Override]
    public function handleClient(
            WebsocketClient $client,
            Request $request,
            Response $response,
    ): void {

        $this->gateway->addClient($client);
        $wrapped_client = SendNostr::send($client, $this->log);
        $client_subscriptions = new Relay\Subscriptions($wrapped_client);
        foreach ($client as $message) {
            $payload = (string) $message;
            $this->log->debug('Received message: ' . $payload);
            try {
                each(($this->incoming)($client_subscriptions, Message::decode($payload)), $wrapped_client);
            } catch (\InvalidArgumentException $ex) {
                $wrapped_client(Factory::notice($ex->getMessage()));
            }
        }
    }
}
