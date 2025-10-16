<?php

namespace nostriphant\Transpher\Relay;

use nostriphant\NIP01\Message;
use nostriphant\NIP01\Transmission;

readonly class MessageHandler implements \nostriphant\Transpher\Amp\MessageHandler {
    
    private Subscriptions $subscriptions;
    
    public function __construct(private Incoming $incoming, private Transmission $transmission) {
        $this->subscriptions = new Subscriptions($transmission);
    }
    
    #[\Override]
    public function __invoke(string $json) : void {
        try {
            foreach (($this->incoming)($this->subscriptions, Message::decode($json)) as $reply) {
                ($this->transmission)($reply);
            }
        } catch (\InvalidArgumentException $ex) {
            ($this->transmission)(Message::notice($ex->getMessage()));
        }
    }       
}
