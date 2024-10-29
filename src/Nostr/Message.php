<?php

namespace nostriphant\Transpher\Nostr;

readonly class Message {

    public mixed $payload;

    public function __construct(public string $type, mixed ...$payload) {
        $this->payload = $payload;
    }

    public function __invoke(): array {
        $payload = $this->payload;
        array_unshift($payload, $this->type);
        return $payload;
    }

    public function __toString(): string {
        return \nostriphant\Transpher\Nostr::encode($this());
    }
}
