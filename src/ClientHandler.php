<?php

namespace rikmeijer\Transpher;
use function \Functional\each;
use \Psr\Log\LoggerInterface; 
use \rikmeijer\Transpher\Relay;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\WebsocketClient;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use rikmeijer\Transpher\Relay\Incoming\Context;

/**
 * Description of ClientHandler
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class ClientHandler implements WebsocketClientHandler {
    public function __construct(
        private Relay $relay,
        private LoggerInterface $log,
        private WebsocketGateway $gateway
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
            $this->log->debug('Received message: ' . $payload);

            $wrapped_client = SendNostr::send($client, $this->log);

            $handler = ($this->relay)(new Context(
                    relay: $wrapped_client
            ));
            each($handler($payload), $wrapped_client);
        }
    }
}
