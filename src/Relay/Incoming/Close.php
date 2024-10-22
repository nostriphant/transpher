<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Subscriptions;
use rikmeijer\Transpher\Nostr\Message\Factory;

/**
 * Description of Close
 *
 * @author rmeijer
 */
readonly class Close implements Incoming {

    public function __construct(private string $subscription_id) {
        
    }

    #[\Override]
    static function fromMessage(array $message): callable {
        if (count($message) < 2) {
            throw new \InvalidArgumentException('Missing subscription ID');
        }

        return fn(): self => new self($message[1]);
    }

    #[\Override]
    public function __invoke(): \Generator {
        Subscriptions::unsubscribe($this->subscription_id);
        yield Factory::closed($this->subscription_id);
    }
}
