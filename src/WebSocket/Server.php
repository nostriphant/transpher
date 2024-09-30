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
        $subscription_handler = fn() => yield from match(func_num_args()) {
            0 => self::relay($this->events, $others, $payload[1]),
            1 => self::closeSubscriptions($from, $this->events, func_get_arg(0)),
            default => self::subscribe($from, $this->events, ...func_get_args())
        };

        foreach(NServer::listen($payload, $subscription_handler) as $reply_message) {
            $reply($reply_message);
        }
    }
    
    static function closeSubscriptions(Connection $from, array|\ArrayAccess &$events, string $subscriptionId) {
        $subscriptions = $from->getMeta('subscriptions')??[];
        unset($subscriptions[$subscriptionId]);
        $from->setMeta('subscriptions', $subscriptions);
        yield Message::closed($subscriptionId);
    }
    
    static function subscribe(Connection $from, array|\ArrayAccess &$events, string $subscriptionId, callable $subscription) {
        $subscriptions = $from->getMeta('subscriptions')??[];
        $subscriptions[$subscriptionId] = $subscription;
        yield from map(filter($events, $subscription), fn(array $event) => Message::requestedEvent($subscriptionId, $event));
        yield Message::eose($subscriptionId);
        $from->setMeta('subscriptions', $subscriptions);
    }
    
    static function relay(array|\ArrayAccess &$events, array $others, array $event) {
        $events[] = $event;
        each($others, fn(callable $other) => $other($event));
        yield Message::accept($event['id']);
    }
}
