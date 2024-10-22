<?php

namespace rikmeijer\Transpher;

use rikmeijer\Transpher\Nostr\Message\Factory;
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
    
    
    public function __construct(private Relay\Incoming\Factory $factory) {
        
    }
    
    public function __invoke(Sender $relay): callable {
        $factory = ($this->factory)($relay);
        return function (string $payload) use ($factory): \Generator {
            $message = \rikmeijer\Transpher\Nostr::decode($payload);
            if (is_null($message)) {
                yield Factory::notice('Invalid message');
            } else {
                try {
                    $incoming = $factory($message);
                    yield from $incoming();
                } catch (\InvalidArgumentException $ex) {
                    yield Factory::notice($ex->getMessage());
                }
            }
        };
    }
}
