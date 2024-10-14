<?php

namespace rikmeijer\Transpher;

use rikmeijer\Transpher\Nostr\Message;
use rikmeijer\Transpher\Nostr\Event;
use rikmeijer\Transpher\Relay\Subscriptions;
use rikmeijer\Transpher\Relay\Sender;

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
    
    public function __invoke(string $payload, Sender $relay) : \Generator {
        $message = \rikmeijer\Transpher\Nostr::decode($payload);
        if (is_null($message)) {
            yield Message::notice('Invalid message');
        } else {
            $type = array_shift($message);
            switch (strtoupper($type)) {
                case 'EVENT': 
                    $event = new Event(...$message[0]);
                    $this->events[] = $event;
                    Subscriptions::apply($event);
                    yield Message::accept($message[0]['id']);
                    break;

                case 'CLOSE': 
                    if (count($message) < 1) {
                        yield Message::notice('Missing subscription ID');
                    } else {
                        Subscriptions::unsubscribe($message[0]);
                        yield Message::closed($message[0]);
                    }
                    break;

                case 'REQ':
                    if (count($message) < 2) {
                        yield Message::notice('Invalid message');
                        break;
                    } 
                    
                    $subscription_id = array_shift($message);
                    if (empty($message[0])) {
                        yield Message::closed($subscription_id, 'Subscription filters are empty');
                    } else {
                        $subscription = Subscriptions::subscribe($relay, $subscription_id, ...$message);
                        $subscribed_events = call_user_func($this->events, $subscription);
                        yield from $subscribed_events($subscription_id);
                        yield Message::eose($subscription_id);
                    }
                    break;

                default: 
                    yield Message::notice('Message type ' . $type . ' not supported');
                    break;
            }
        }
    }
}
