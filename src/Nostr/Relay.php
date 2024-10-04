<?php

namespace Transpher\Nostr;

use \Transpher\Nostr\Message;
use \Transpher\Filters;
use \Transpher\Process;
use function \Functional\map, \Functional\each, \Functional\filter;

/**
 * Description of Server
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Relay {
    
    static function boot(string $address, array $env) : Process {
        $cmd = [PHP_BINARY, ROOT_DIR . DIRECTORY_SEPARATOR . 'relay.php', $address];
        list($ip, $port) = explode(':', $address);
        return new Process('relay-' . $port, $cmd, $env, fn(string $line) => str_contains($line, 'Listening on http://127.0.0.1:'.$port.'/'));
    }
    
    
    public function __construct(private array|\ArrayAccess $events) {
        
    }
    
    public function __invoke(callable $relay, callable $unsubscribe, callable $subscribe, array $message) : \Generator {
        $type = array_shift($message);
        switch (strtoupper($type)) {
            case 'EVENT': 
                yield from self::relay($relay, $this->events, ...$message);
                break;
            case 'CLOSE': 
                yield from self::closeSubscription($unsubscribe, ...$message);
                break;
            case 'REQ':
                if (count($message) < 2) {
                    yield Message::notice('Invalid message');
                } elseif (empty($message[1])) {
                    yield Message::closed($message[0], 'Subscription filters are empty');
                } else {
                    yield from self::subscribe($subscribe, $this->events, $message[0], Filters::constructFromPrototype($message[1]));
                }
                break;
            default: 
                yield Message::notice('Message type ' . $type . ' not supported');
                break;
        }
    }
    
    static function closeSubscription(callable $unsubscribe, string $subscriptionId) {
        $unsubscribe($subscriptionId);
        yield Message::closed($subscriptionId);
    }
    
    static function subscribe(callable $subscribe, array|\ArrayAccess &$events, string $subscriptionId, callable $subscription) {
        $subscribe($subscriptionId, $subscription);
        
        yield from map(filter($events, $subscription), fn(array $event) => Message::requestedEvent($subscriptionId, $event));
        yield Message::eose($subscriptionId);
    }
    
    static function relay(callable $relay, array|\ArrayAccess &$events, array $event) {
        $events[] = $event;
        $relay($event);
        yield Message::accept($event['id']);
    }
}
