<?php


namespace Transpher\WebSocket;

use Transpher\Nostr;
use Functional\Functional;
use WebSocket\Server as WSServer;
use Transpher\Nostr\Server as NServer;
use WebSocket\Connection;
use function \Functional\reject, \Functional\first, \Functional\each, \Functional\if_else, \Functional\map, \Functional\filter;

/**
 * Description of WebSocket
 *
 * @author Rik Meijer <rmeijer@wemanity.com>
 */
class Server {
    
    public function __construct(private WSServer $server) {
        $server
            ->addMiddleware(new \WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new \WebSocket\Middleware\PingResponder());
    }
    
    public function start(array|\ArrayAccess $events, \Psr\Log\LoggerInterface $log) {
        $this->server->onText(function (WSServer $server, Connection $from, \WebSocket\Message\Message $message) use (&$events, $log) {
            $log->info('Received message: ' . $message->getPayload());

            $payload = json_decode($message->getPayload(), true);

            $edit_subscriptions = self::subscriptionEditor($events, $from);
            $relay_event_to_subscribers = self::eventRelayer($events, $from, $server);

            $subscription_handler = function() use ($relay_event_to_subscribers, $edit_subscriptions) {
                if (func_num_args() === 1) {
                    $relay_event_to_subscribers(func_get_arg(0));
                } else {
                    yield from $edit_subscriptions(...func_get_args());
                }
            };

            $reply = Nostr::wrap([$from, 'text']);
            foreach(NServer::listen($payload, $subscription_handler, $log) as $reply_message) $reply($reply_message);
        });
        $this->server->start();
    }
    
    static function subscriptionEditor(array|\ArrayAccess &$events, Connection $from) {
        return function(string $subscriptionId, ?callable $subscription) use (&$events, $from) {
            $subscriptions = $from->getMeta('subscriptions')??[];
            if (is_null($subscription)) {
                unset($subscriptions[$subscriptionId]);
            } else {
                $subscriptions[$subscriptionId] = $subscription;
                yield from map(filter($events, $subscription), fn(array $event) => Nostr::subscribedEvent($subscriptionId, $event));
            }
            $from->setMeta('subscriptions', $subscriptions);
        };
    }
    
    static function eventRelayer(array|\ArrayAccess &$events, Connection $from, WSServer $server) {
        $isOther = fn(Connection $client) => $client === $from;
        $others = reject($server->getConnections(), $isOther);
        return function(array $event) use (&$events, $others) : void {
            $events[] = $event;
            each($others, fn(Connection $other) => first(
                $other->getMeta('subscriptions')??[], 
                fn(callable $subscription, string $subscriptionId) => if_else(
                    $subscription, 
                    fn($event) => NServer::relay(Nostr::wrap([$other, 'text']), $subscriptionId, $event),
                    Functional::false
                )($event)
            ));
        };
    }
}
