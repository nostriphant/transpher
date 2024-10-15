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
                    yield from $incoming()($this->events);
                    break;

                case 'CLOSE':
                    try {
                        $incoming = Relay\Incoming\Close::fromMessage($message);
                    } catch (\InvalidArgumentException $ex) {
                        yield Message::notice($ex->getMessage());
                        break;
                    }
                    yield from $incoming()();
                    break;

                case 'REQ':
                    try {
                        $incoming = Relay\Incoming\Req::fromMessage($message);
                    } catch (\InvalidArgumentException $ex) {
                        yield Message::notice($ex->getMessage());
                        break;
                    }
                    yield from $incoming()($this->events, $relay);
                    break;

                default: 
                    yield Message::notice('Message type ' . $message[0] . ' not supported');
                    break;
            }
        }
    }
}
