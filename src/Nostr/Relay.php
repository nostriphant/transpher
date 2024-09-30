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
    
    static function listen(array $message, callable $subscriptions) {
        $type = array_shift($message);
        if (is_callable([self::class, $type]) === false) {
            yield Message::notice('Message type ' . $type . ' not supported');
        } else {
            try {
                yield from call_user_func([self::class, $type], $subscriptions, ...$message);
            } catch (\ArgumentCountError $ex) {
                yield Message::notice('Invalid message');
            }
        }
    }
    
    static function event(callable $subscriptions, array $event) : \Generator { 
        yield from $subscriptions();
    }
    
    static function close(callable $subscriptions, string $subscriptionId) : \Generator {
        yield from $subscriptions($subscriptionId);
    }
    
    static function req(callable $subscriptions, string $subscriptionId, array $subscription) : \Generator {
        if (empty($subscription)) {
            yield Message::closed($subscriptionId, 'Subscription filters are empty');
        } else {
            yield from $subscriptions($subscriptionId, Filters::constructFromPrototype($subscription));
        }
    }
    
    static function relay(callable $to, string $subscriptionId) : callable {
        return fn(array $event) => $to(
            Message::requestedEvent($subscriptionId, $event),
            Message::eose($subscriptionId)
        );
    }
}
