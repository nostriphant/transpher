<?php

namespace Transpher\Nostr;

use \Transpher\Nostr\Message;
use \Transpher\Filters;
use \Transpher\Process;
use Transpher\Nostr\Relay\Subscriptions;
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
    
    public function __invoke(string $payload, callable $relay) : \Generator {
        $message = \Transpher\Nostr::decode($payload);
        if (is_null($message)) {
            yield Message::notice('Invalid message');
        }
        
        $type = array_shift($message);
        switch (strtoupper($type)) {
            case 'EVENT': 
                $this->events[] = $message[0];
                Subscriptions::makeStore()($message[0]);
                yield Message::accept($message[0]['id']);
                break;
            
            case 'CLOSE': 
                Subscriptions::unsubscribe($message[0]);
                yield Message::closed($message[0]);
                break;
            
            case 'REQ':
                if (count($message) < 2) {
                    yield Message::notice('Invalid message');
                } elseif (empty($message[1])) {
                    yield Message::closed($message[0], 'Subscription filters are empty');
                } else {
                    $subscription = Filters::constructFromPrototype($message[1]);
                    Subscriptions::subscribe($message[0], $subscription, function(string $subscriptionId, array $event) use ($relay) : bool {
                        $relay(\Transpher\Nostr::encode(\Transpher\Nostr\Message::requestedEvent($subscriptionId, $event)));
                        $relay(\Transpher\Nostr::encode(\Transpher\Nostr\Message::eose($subscriptionId)));
                        return true;
                    });
                    Subscriptions::makeStore()($message[0]);
                }
                break;
                
            default: 
                yield Message::notice('Message type ' . $type . ' not supported');
                break;
        }
    }
}
