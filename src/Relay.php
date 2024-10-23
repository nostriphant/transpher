<?php

namespace rikmeijer\Transpher;

use function \Functional\each;
use \Psr\Log\LoggerInterface;
use \rikmeijer\Transpher\Relay;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\Server\WebsocketClientGateway;
use Amp\Websocket\WebsocketClient;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use rikmeijer\Transpher\Relay\Incoming\Context;
use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\SendNostr;
use rikmeijer\Transpher\Relay\Store;

readonly class Relay implements WebsocketClientHandler {

    public function __construct(private Store $store,
            private LoggerInterface $log,
            private WebsocketGateway $gateway = new WebsocketClientGateway()) {
        
    }

    #[\Override]
    public function handleClient(
            WebsocketClient $client,
            Request $request,
            Response $response,
    ): void {
        $this->gateway->addClient($client);
        foreach ($client as $message) {
            $payload = (string) $message;
            $this->log->debug('Received message: ' . $payload);
            $wrapped_client = SendNostr::send($client, $this->log);
            try {
                $context = new Context(
                        events: $this->store,
                        relay: $wrapped_client
                );

                $incoming = Relay\Incoming\Factory::make(\rikmeijer\Transpher\Nostr::decode($payload));

                each($incoming($context), $wrapped_client);
            } catch (\InvalidArgumentException $ex) {
                $wrapped_client(Factory::notice($ex->getMessage()));
            }
        }
    }
}
