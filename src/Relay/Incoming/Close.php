<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Incoming;
use rikmeijer\Transpher\Relay\Subscriptions;
use rikmeijer\Transpher\Nostr\Message;

/**
 * Description of Close
 *
 * @author rmeijer
 */
class Close implements Incoming {

    public function __construct(private string $subscription_id) {
        
    }

    #[\Override]
    static function fromMessage(array $message): self {
        if (count($message) < 2) {
            throw new \InvalidArgumentException('Missing subscription ID');
        }
        return new self($message[1]);
    }

    #[\Override]
    public function __invoke(): callable {
        return function (): \Generator {
            Subscriptions::unsubscribe($this->subscription_id);
            yield Message::closed($this->subscription_id);
        };
    }
}
