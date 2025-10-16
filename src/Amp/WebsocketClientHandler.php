<?php

namespace nostriphant\Transpher\Amp;

use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\WebsocketClient;
use nostriphant\NIP01\Message;
use nostriphant\Transpher\Relay\Subscriptions;
use nostriphant\NIP01\Transmission;

use nostriphant\Transpher\Relay\Incoming;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

class WebsocketClientHandler implements \Amp\Websocket\Server\WebsocketClientHandler {
    public function __construct(private Incoming $incoming,  private WebsocketGateway $gateway) {

    }

    #[\Override]
    public function handleClient(
            WebsocketClient $client,
            Request $request,
            Response $response,
    ): void {

        $this->gateway->addClient($client);
        $wrapped_client = new class($client) implements Transmission {
            public function __construct(private WebsocketClient $client) {

            }
            #[\Override]
            public function __invoke(Message $message): bool {
                $this->client->sendText($message);
                return true;
            }

        };

        $client_subscriptions = new Subscriptions($wrapped_client);
        foreach ($client as $message) {
            try {
                foreach (($this->incoming)($client_subscriptions, Message::decode($message)) as $reply) {
                    $wrapped_client($reply);
                }
            } catch (\InvalidArgumentException $ex) {
                $wrapped_client(Message::notice($ex->getMessage()));
            }
        }
    }
}
