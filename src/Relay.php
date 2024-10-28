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

class Relay implements WebsocketClientHandler {

    private Context $context;

    private array $subscriptions = [];

    public function __construct(private Store $store,
            private LoggerInterface $log,
            private WebsocketGateway $gateway = new WebsocketClientGateway()) {
        $this->context = new Context(
                events: $this->store,
        );
    }

    #[\Override]
    public function handleClient(
            WebsocketClient $client,
            Request $request,
            Response $response,
    ): void {
        $this->gateway->addClient($client);
        $wrapped_client = SendNostr::send($client, $this->log);
        $client_context = Context::merge(new Context(
                        subscriptions: new Relay\Subscriptions($this->subscriptions),
                        relay: $wrapped_client
                ), $this->context);
        foreach ($client as $message) {
            $payload = (string) $message;
            $this->log->debug('Received message: ' . $payload);
            self::handle($payload, $client_context);
        }
    }

    static function handle(string $payload, Context $context) {
        try {
            $message = \rikmeijer\Transpher\Nostr::decode($payload);
            $incoming = Relay\Incoming\Factory::make($message);
            each($incoming($context), $context->relay);
        } catch (\InvalidArgumentException $ex) {
            ($context->relay)(Factory::notice($ex->getMessage()));
        }
    }
}
