<?php

namespace Transpher\Nostr;
use Transpher\Nostr;
use Transpher\Key;

/**
 * Description of Event
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Event {
    
    private array $tags;
    
    public function __construct(private int $created_at, private int $kind, private string $content, array ...$tags) {
        $this->tags = $tags;
    }

    public function __invoke(Key $private_key): array {
        $id = hash('sha256', Nostr::encode([0, $private_key(Key::public()), $this->created_at, $this->kind, $this->tags, $this->content]));
        return [
            "id" => $id,
            "pubkey" => $private_key(Key::public()),
            "created_at" => $this->created_at,
            "kind" => $this->kind,
            "tags" => $this->tags,
            "content" => $this->content,
            "sig" => $private_key(Key::signer($id))
        ];
    }
    
}
