<?php

namespace rikmeijer\Transpher\Nostr;
use rikmeijer\Transpher\Nostr\NIP44;
use rikmeijer\Transpher\Key;

/**
 * Description of Decrypter
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Decrypter {
    private NIP44\ConversationKey $conversation_key;
    public function __construct(Key $recipient_key, string $sender_pubkey) {
        $this->conversation_key = new NIP44\ConversationKey($recipient_key, $sender_pubkey);
    }
    public function __invoke(string $message) : string {
        return NIP44::decrypt($message, call_user_func($this->conversation_key));
    }
}
