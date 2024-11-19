<?php

namespace nostriphant\Transpher\Nostr;
use nostriphant\Transpher\Nostr;
use nostriphant\NIP01\Key;

readonly class Rumor {
    
    public string $id;
    
    public function __construct(public int $created_at, public string $pubkey, public int $kind, public string $content, public array $tags) {
        $this->id = hash('sha256', Nostr::encode([0, $this->pubkey, $this->created_at, $this->kind, $this->tags, $this->content]));
    }

    public function __invoke(Key $private_key): Event {
        return new Event(...[
            "id" => $this->id,
            "pubkey" => $this->pubkey,
            "created_at" => $this->created_at,
            "kind" => $this->kind,
            "tags" => $this->tags,
            "content" => $this->content,
            "sig" => $private_key(Key::signer($this->id))
        ]);
    }
    
}
