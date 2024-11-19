<?php

namespace nostriphant\Transpher\Nostr;

use nostriphant\NIP01\Key;
use nostriphant\NIP44\ConversationKey;
use nostriphant\NIP44\Functions as NIP44;

readonly class Decrypter {
    private ConversationKey $conversation_key;

    public function __construct(Key $recipient_key, string $sender_pubkey) {
        $this->conversation_key = new ConversationKey($recipient_key, $sender_pubkey);
    }
    public function __invoke(string $message) : string {
        return NIP44::decrypt($message, call_user_func($this->conversation_key));
    }
}
