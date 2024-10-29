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
use nostriphant\Transpher\Relay\Incoming\Context;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\SendNostr;
use nostriphant\Transpher\Relay\Store;

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
                        relay: $wrapped_client,
                        reply: $wrapped_client
                ), $this->context);
        foreach ($client as $message) {
            $payload = (string) $message;
            $this->log->debug('Received message: ' . $payload);
            self::handle($payload, $client_context);
        }
    }

    static function handle(string $payload, Context $context) {
        try {
            $message = \nostriphant\Transpher\Nostr::decode($payload);
            $incoming = Relay\Incoming\Factory::make($message);
            each($incoming($context), $context->reply);
        } catch (\InvalidArgumentException $ex) {
            ($context->reply)(Factory::notice($ex->getMessage()));
        }
    }
}
