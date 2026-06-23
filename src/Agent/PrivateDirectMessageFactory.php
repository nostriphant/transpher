<?php


namespace nostriphant\Transpher\Agent;

use nostriphant\NIP01\Key;
use nostriphant\NIP17\PrivateDirect;

class PrivateDirectMessageFactory {
        
    public function __construct(private Key $nsec, private string $recipient_pubkey, private \Psr\Log\LoggerInterface $logger) {
        $logger->debug('Create transmitter to ' . $recipient_pubkey);
    }
    
    public function __invoke(callable $send): callable {
        return function(string $message) use ($send) { 
            $this->logger->debug('Sending encrypted message "' . $message . '".');
            $gift = \nostriphant\Functional\Partial::left([PrivateDirect::class,'make'], $this->nsec, $this->recipient_pubkey);
            return $send($gift($message), function(bool $accepted, string $reason) {
                $this->logger->debug('Message has' . ($accepted?'':' not ("'.$reason.'")') .' been accepted.');
            });
        };
    }
    
}
