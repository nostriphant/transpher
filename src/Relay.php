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
use nostriphant\Transpher\Relay\Store;

class Relay implements WebsocketClientHandler {

    private array $subscriptions = [];

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
        $wrapped_client = SendNostr::send($client, $this->log);
        $client_subscriptions = new Relay\Subscriptions($this->subscriptions, $wrapped_client);
        foreach ($client as $message) {
            $payload = (string) $message;
            $this->log->debug('Received message: ' . $payload);
            try {
                $incoming = new Relay\Incoming($this->store, $client_subscriptions);
                each($incoming(Nostr\Message::decode($payload)), $wrapped_client);
            } catch (\InvalidArgumentException $ex) {
                $wrapped_client(Factory::notice($ex->getMessage()));
            }
        }
    }
}
