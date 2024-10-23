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

readonly class Relay implements WebsocketClientHandler {

    public function __construct(private Context $context,
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

            $handler = $this(new Context(
                relay: $wrapped_client
                    ));
            each($handler($payload), $wrapped_client);
        }
    }

    public function __invoke(Context $context): callable {
        $factory = Relay\Incoming\Factory::make(Context::merge($context, $this->context));
        return function (string $payload) use ($factory): \Generator {
            try {
                yield from $factory(\rikmeijer\Transpher\Nostr::decode($payload));
            } catch (\InvalidArgumentException $ex) {
                yield Factory::notice($ex->getMessage());
            }
        };
    }
}
