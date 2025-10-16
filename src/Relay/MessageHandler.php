<?php

namespace nostriphant\Transpher\Relay;


class MessageHandler implements \nostriphant\Transpher\Amp\MessageHandler {
    public function __construct(private Incoming $incoming, private Subscriptions $subscriptions) {

    }
    
    #[\Override]
    public function __invoke(string $json) : \Traversable {
       return ($this->incoming)($this->subscriptions, \nostriphant\NIP01\Message::decode($json));
    }       
}
