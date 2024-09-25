<?php

namespace Transpher\Nostr\Message;

/**
 * Description of PrivateDirect
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class PrivateDirect {
    
    public function __construct(private \Transpher\Key $private_key) {}
    public function __invoke(string $recipient_pubkey, string $message) : array {
        $unsigned_event = new \Transpher\Nostr\Event(time(), 14, $message, ['p', $recipient_pubkey]);
        $direct_message = $unsigned_event($this->private_key);
        unset($direct_message['sig']);
        return ['EVENT', \Transpher\Nostr\Event\Gift::wrap($recipient_pubkey, \Transpher\Nostr\Event\Seal::close($this->private_key, $recipient_pubkey, $direct_message))];
    }
}
