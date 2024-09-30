<?php

namespace Transpher\Nostr;

use \Transpher\Nostr\Message;
use \Transpher\Filters;

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
    
    static function listen(array $message, callable $relay, callable $close, callable $subscribe) {
        $type = array_shift($message);
        switch (strtoupper($type)) {
            case 'EVENT': 
                yield from $relay(...$message);
                break;
            case 'CLOSE': 
                yield from $close(...$message);
                break;
            case 'REQ':
                if (count($message) < 2) {
                    yield Message::notice('Invalid message');
                } elseif (empty($message[1])) {
                    yield Message::closed($message[0], 'Subscription filters are empty');
                } else {
                    yield from $subscribe($message[0], Filters::constructFromPrototype($message[1]));
                }
                break;
            default: 
                yield Message::notice('Message type ' . $type . ' not supported');
                break;
        }
    }
    
    static function relay(callable $to, string $subscriptionId) : callable {
        return fn(array $event) => $to(
            Message::requestedEvent($subscriptionId, $event),
            Message::eose($subscriptionId)
        );
    }
}
