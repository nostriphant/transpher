<?php

namespace nostriphant\Transpher\Nostr;

/**
 * Description of Encrypter
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Encrypter {
    private NIP44\ConversationKey $conversation_key;
    public function __construct(Key $sender_key, string $recipient_pubkey) {
        $this->conversation_key = new NIP44\ConversationKey($sender_key, $recipient_pubkey);
    }
    public function __invoke(string $message) : string {
        return NIP44::encrypt($message, call_user_func($this->conversation_key), random_bytes(32));
    }
}
