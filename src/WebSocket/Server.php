<?php


namespace Transpher\WebSocket;

use Transpher\Nostr;
use WebSocket\Server as WSServer;
use Transpher\Nostr\Relay as NServer;
use WebSocket\Connection;
use Transpher\Nostr\Message;
use function \Functional\each, \Functional\map, \Functional\filter;

/**
 * Description of WebSocket
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Server {
    
    public function __construct(callable $onjson, private \Psr\Log\LoggerInterface $log, array|\ArrayAccess $events) {
        $onjson(function(Connection $from, array $others, array $payload) use (&$events) {
            $edit_subscriptions = self::subscriptionEditor($from);
           
            $relay_event_to_subscribers = self::eventRelayer($events, $others);

            $subscription_handler = function() use (&$events, $relay_event_to_subscribers, $edit_subscriptions) {
                if (func_num_args() === 1) {
                    $relay_event_to_subscribers(func_get_arg(0));
                } else {
                    yield from $edit_subscriptions($events, ...func_get_args());
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
    
    
    static function subscriptionEditor(Connection $from) {
        return function(array|\ArrayAccess &$events, string $subscriptionId, ?callable $subscription) use ($from) {
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
