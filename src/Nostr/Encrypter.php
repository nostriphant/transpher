<?php

namespace rikmeijer\Transpher\Nostr;
use rikmeijer\Transpher\Nostr\NIP44;
use rikmeijer\Transpher\Key;

/**
 * Description of Encrypter
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Encrypter {
    private string $conversation_key;
    public function __construct(Key $sender_key, string $recipient_pubkey) {
        $this->conversation_key = NIP44::getConversationKey($sender_key, $recipient_pubkey);
    }
    public function __invoke(string $message) : string {
        return NIP44::encrypt($message, new NIP44\MessageKeys($this->conversation_key), random_bytes(32));
    }
}
