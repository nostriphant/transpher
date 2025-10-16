<?php

namespace nostriphant\Transpher\Amp;

use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\WebsocketClient;
use nostriphant\NIP01\Message;
use nostriphant\NIP01\Transmission;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

class WebsocketClientHandler implements \Amp\Websocket\Server\WebsocketClientHandler {
    public function __construct(private MessageHandlerFactory $incoming_factory,  private WebsocketGateway $gateway) {

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

        
        $message_handler = ($this->incoming_factory)($wrapped_client);
        foreach ($client as $message) {
            try {
                foreach ($message_handler($message) as $reply) {
                    $wrapped_client($reply);
                }
            } catch (\InvalidArgumentException $ex) {
                $wrapped_client(Message::notice($ex->getMessage()));
            }
        }
    }
}
