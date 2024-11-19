<?php

namespace nostriphant\Transpher\Nostr;

use nostriphant\NIP01\Key;
use nostriphant\NIP44\ConversationKey;
use nostriphant\NIP44\Functions as NIP44;

readonly class Encrypter {
    private ConversationKey $conversation_key;

    public function __construct(Key $sender_key, string $recipient_pubkey) {
        $this->conversation_key = new ConversationKey($sender_key, $recipient_pubkey);
    }
    public function __invoke(string $message) : string {
        return NIP44::encrypt($message, call_user_func($this->conversation_key), random_bytes(32));
    }
}
