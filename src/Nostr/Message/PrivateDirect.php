<?php

namespace rikmeijer\Transpher\Nostr\Message;
use rikmeijer\Transpher\Key;
use rikmeijer\Transpher\Nostr\Event\Gift;
use rikmeijer\Transpher\Nostr\Event\Seal;

/**
 * Description of PrivateDirect
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class PrivateDirect {
    
    public function __construct(private Key $private_key) {}
    public function __invoke(string $recipient_pubkey, string $message) : array {
        return ['EVENT', get_object_vars(Gift::wrap($recipient_pubkey, Seal::close($this->private_key, $recipient_pubkey, new \rikmeijer\Transpher\Nostr\Rumor(
            pubkey: call_user_func($this->private_key, Key::public()),
            created_at: time(), 
            kind: 14, 
            content: $message, 
            tags: [['p', $recipient_pubkey]]
        ))))];
    }
}
