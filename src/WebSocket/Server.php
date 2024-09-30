<?php


namespace Transpher\WebSocket;

use Transpher\Nostr;
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
    
    public function __construct(private \Psr\Log\LoggerInterface $log, private array|\ArrayAccess $events) {
        
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
    
    public function __invoke(Connection $from, callable $reply, array $others, array $payload) {
        $callbacks = [
            'relay' => \Transpher\WebSocket\Server::relay($others, $this->events),
            'close' => \Transpher\WebSocket\Server::closeSubscription($from),
            'subscribe' => \Transpher\WebSocket\Server::subscribe($from, $this->events)
        ];
        
        foreach(NServer::listen($payload, ...$callbacks) as $reply_message) {
            $reply($reply_message);
        }
    }
    
    static function closeSubscription(Connection $from) : callable {
        return function(string $subscriptionId) use ($from) {
            $subscriptions = $from->getMeta('subscriptions')??[];
            unset($subscriptions[$subscriptionId]);
            $from->setMeta('subscriptions', $subscriptions);
            yield Message::closed($subscriptionId);
        };
    }
    
    static function subscribe(Connection $from, array|\ArrayAccess &$events) : callable {
        return function(string $subscriptionId, callable $subscription) use ($from, &$events) {
            $subscriptions = $from->getMeta('subscriptions')??[];
            $subscriptions[$subscriptionId] = $subscription;
            yield from map(filter($events, $subscription), fn(array $event) => Message::requestedEvent($subscriptionId, $event));
            yield Message::eose($subscriptionId);
            $from->setMeta('subscriptions', $subscriptions);
        };
    }
    
    static function relay(array $others, array|\ArrayAccess &$events) : callable {
        return function(array $event) use (&$events, $others) {
            $events[] = $event;
            each($others, fn(callable $other) => $other($event));
            yield Message::accept($event['id']);
        };
    }
}
