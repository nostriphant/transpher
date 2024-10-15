<?php

namespace rikmeijer\Transpher;

use rikmeijer\Transpher\Nostr\Message;
use rikmeijer\Transpher\Relay\Subscriptions;
use rikmeijer\Transpher\Relay\Sender;
use rikmeijer\Transpher\Relay\Store;

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
    
    
    public function __construct(private array|Store $events) {
        
    }
    
    public function __invoke(string $payload, Sender $relay) : \Generator {
        $message = \rikmeijer\Transpher\Nostr::decode($payload);
        if (is_null($message)) {
            yield Message::notice('Invalid message');
        } else {
            switch (strtoupper($message[0])) {
                case 'EVENT':
                    $incoming = Relay\Incoming\Event::fromMessage($message);
                    yield from $incoming($this->events);
                    break;

                case 'CLOSE':
                    try {
                        $incoming = Relay\Incoming\Close::fromMessage($message);
                    } catch (\InvalidArgumentException $ex) {
                        yield Message::notice($ex->getMessage());
                        break;
                    }
                    yield from $incoming($this->events);
                    break;

                case 'REQ':
                    if (count($message) < 3) {
                        yield Message::notice('Invalid message');
                        break;
                    } elseif (empty($message[2])) {
                        yield Message::closed($message[1], 'Subscription filters are empty');
                    } else {
                        $subscription = Subscriptions::subscribe($relay, $message[1], ...array_slice($message, 2));
                        $subscribed_events = call_user_func($this->events, $subscription);
                        yield from $subscribed_events($message[1]);
                        yield Message::eose($message[1]);
                    }
                    break;

                default: 
                    yield Message::notice('Message type ' . $message[0] . ' not supported');
                    break;
            }
        }
    }
}
