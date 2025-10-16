<?php

namespace nostriphant\Transpher\Relay;

readonly class MessageHandlerFactory implements \nostriphant\Transpher\Amp\MessageHandlerFactory {
    
    private Incoming $incoming;
    
    public function __construct(\nostriphant\Stores\Store $store, \nostriphant\Transpher\Files $files) {
        $this->incoming = new Incoming($store, $files);
    }
    
    #[\Override]
    public function __invoke(\nostriphant\NIP01\Transmission $transmission) : \nostriphant\Transpher\Amp\MessageHandler {
        return new MessageHandler($this->incoming, $transmission);
    }
}
