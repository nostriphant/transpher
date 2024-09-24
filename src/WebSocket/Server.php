<?php


namespace Transpher\WebSocket;

use Transpher\Nostr;
use Functional\Functional;
use WebSocket\Server as WSServer;
use Transpher\Nostr\Relay as NServer;
use WebSocket\Connection;
use function \Functional\reject, \Functional\first, \Functional\each, \Functional\if_else, \Functional\map, \Functional\filter;

/**
 * Description of WebSocket
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Server {
    
    public function __construct(private WSServer $server, private \Psr\Log\LoggerInterface $log) {
        $server
            ->addMiddleware(new \WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new \WebSocket\Middleware\PingResponder())
            ->setLogger($log);
    }
    
    private function wrapClient(Connection $client, string $action) : callable {
        return function(array ...$messages) use ($client, $action) : bool {
            foreach ($messages as $message) {
                $encoded_message = Nostr::encode($message);
                $this->log->debug($action . ' message ' . $encoded_message);
                $client->text($encoded_message);
            }
            return true;
        };
    }
    
    private function getOthers(Connection $from) {
        $others = reject($this->server->getConnections(), fn(Connection $client) => $client === $from);
        
        return map($others, fn(Connection $client) => fn(array $event) => first(
            $client->getMeta('subscriptions')??[], 
            fn(callable $subscription, string $subscriptionId) => if_else(
                $subscription, 
                NServer::relay($this->wrapClient($client, 'Relay'), $subscriptionId),
                Functional::false
            )($event)
        ));
    }
    
    public function start(array|\ArrayAccess $events) {
        $this->server->onText(function (WSServer $server, Connection $from, \WebSocket\Message\Message $message) use (&$events) {
            $this->log->info('Received message: ' . $message->getPayload());

            $payload = json_decode($message->getPayload(), true);

            $edit_subscriptions = self::subscriptionEditor($events, $from);
           
            $others = $this->getOthers($from);
            $relay_event_to_subscribers = self::eventRelayer($events, $others);

            $subscription_handler = function() use ($relay_event_to_subscribers, $edit_subscriptions) {
                if (func_num_args() === 1) {
                    $relay_event_to_subscribers(func_get_arg(0));
                } else {
                    yield from $edit_subscriptions(...func_get_args());
                }
            };

            $reply = $this->wrapClient($from, 'Reply');
            foreach(NServer::listen($payload, $subscription_handler) as $reply_message) {
                $reply($reply_message);
            }
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
                yield from map(filter($events, $subscription), fn(array $event) => Nostr::requestedEvent($subscriptionId, $event));
            }
            $from->setMeta('subscriptions', $subscriptions);
        };
    }
    
    static function eventRelayer(array|\ArrayAccess &$events, array $others) {
        return function(array $event) use (&$events, $others) : void {
            $events[] = $event;
            each($others, fn(callable $other) => $other($event));
        };
    }
}
