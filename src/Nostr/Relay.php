<?php

namespace Transpher\Nostr;

use \Transpher\Nostr\Message;
use \Transpher\Filters;
use function \Functional\map, \Functional\each, \Functional\filter;

/**
 * Description of Server
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Relay {
    
    static function boot(int $port, array $env, callable $running) : void {
        $cmd = [PHP_BINARY, ROOT_DIR . DIRECTORY_SEPARATOR . 'relay.php', $port];
        \Transpher\Process::start('relay-' . $port, $cmd, $env, fn(string $line) => str_contains($line, 'Server is running'), $running);
    }
    
    
    public function __construct(private \Psr\Log\LoggerInterface $log, private array|\ArrayAccess $events) {
        
    }
    
    static function wrapClient(\WebSocket\Connection $client, string $action, \Psr\Log\LoggerInterface $log) : callable {
        return function(array ...$messages) use ($client, $action, $log) : bool {
            foreach ($messages as $message) {
                $encoded_message = \Transpher\Nostr::encode($message);
                $log->debug($action . ' message ' . $encoded_message);
                $client->text($encoded_message);
            }
            return true;
        };
    }
    
    public function __invoke(\WebSocket\Connection $from, array $others, array $message) : \Generator {
        $subscriptions = function(?array $subscriptions = null) use ($from) : array {
            if (isset($subscriptions)) {
                $from->setMeta('subscriptions', $subscriptions);
            }
            return $from->getMeta('subscriptions')??[];
        };
        
        $type = array_shift($message);
        switch (strtoupper($type)) {
            case 'EVENT': 
                yield from self::relay($others, $this->events, ...$message);
                break;
            case 'CLOSE': 
                yield from self::closeSubscription($subscriptions, ...$message);
                break;
            case 'REQ':
                if (count($message) < 2) {
                    yield Message::notice('Invalid message');
                } elseif (empty($message[1])) {
                    yield Message::closed($message[0], 'Subscription filters are empty');
                } else {
                    yield from self::subscribe($subscriptions, $this->events, $message[0], Filters::constructFromPrototype($message[1]));
                }
                break;
            default: 
                yield Message::notice('Message type ' . $type . ' not supported');
                break;
        }
    }
    
    static function closeSubscription(callable $client_subscriptions, string $subscriptionId) {
        $subscriptions = $client_subscriptions();
        unset($subscriptions[$subscriptionId]);
        $client_subscriptions($subscriptions);
        yield Message::closed($subscriptionId);
    }
    
    static function subscribe(callable $client_subscriptions, array|\ArrayAccess &$events, string $subscriptionId, callable $subscription) {
        $subscriptions = $client_subscriptions();
        $subscriptions[$subscriptionId] = $subscription;
        $client_subscriptions($subscriptions);
        
        yield from map(filter($events, $subscription), fn(array $event) => Message::requestedEvent($subscriptionId, $event));
        yield Message::eose($subscriptionId);
    }
    
    static function relay(array $others, array|\ArrayAccess &$events, array $event) {
        $events[] = $event;
        each($others, fn(callable $other) => $other($event));
        yield Message::accept($event['id']);
    }
}
