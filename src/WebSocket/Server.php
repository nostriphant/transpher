<?php


namespace Transpher\WebSocket;

use Transpher\Nostr;
use Functional\Functional;
use WebSocket\Server as WSServer;
use Transpher\Nostr\Relay as NServer;
use WebSocket\Connection;
use Transpher\Nostr\Message;
use function \Functional\first, \Functional\each, \Functional\if_else, \Functional\map, \Functional\filter;

/**
 * Description of WebSocket
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Server {
    
    public function __construct(private WSServer $server, private \Psr\Log\LoggerInterface $log, array|\ArrayAccess $events) {
        $this->server->onJson(function(Connection $from, array $payload) use ($log, &$events) {
            $edit_subscriptions = self::subscriptionEditor($events, $from);
           
            $others = $this->server->getOthers($from, fn(Connection $client) => fn(array $event) => first(
                $client->getMeta('subscriptions')??[], 
                fn(callable $subscription, string $subscriptionId) => if_else(
                    $subscription, 
                    NServer::relay(self::wrapClient($client, 'Relay', $this->log), $subscriptionId),
                    Functional::false
                )($event)
            ));
            $relay_event_to_subscribers = self::eventRelayer($events, $others);

            $subscription_handler = function() use ($relay_event_to_subscribers, $edit_subscriptions) {
                if (func_num_args() === 1) {
                    $relay_event_to_subscribers(func_get_arg(0));
                } else {
                    yield from $edit_subscriptions(...func_get_args());
                }
            };

            $reply = self::wrapClient($from, 'Reply', $this->log);
            foreach(NServer::listen($payload, $subscription_handler) as $reply_message) {
                $reply($reply_message);
            }
        });
    }
    
    static function wrapClient(Connection $client, string $action, \Psr\Log\LoggerInterface $log) : callable {
        return function(array ...$messages) use ($client, $action, $log) : bool {
            foreach ($messages as $message) {
                $encoded_message = Nostr::encode($message);
                $log->debug($action . ' message ' . $encoded_message);
                $client->text($encoded_message);
            }
            return true;
        };
    }
    
    public function start() {
        $this->server->start();
    }
    
    static function subscriptionEditor(array|\ArrayAccess &$events, Connection $from) {
        return function(string $subscriptionId, ?callable $subscription) use (&$events, $from) {
            $subscriptions = $from->getMeta('subscriptions')??[];
            if (is_null($subscription)) {
                unset($subscriptions[$subscriptionId]);
            } else {
                $subscriptions[$subscriptionId] = $subscription;
                yield from map(filter($events, $subscription), fn(array $event) => Message::requestedEvent($subscriptionId, $event));
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
