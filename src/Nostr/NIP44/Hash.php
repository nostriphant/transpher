<?php

namespace nostriphant\Transpher\Nostr\NIP44;

/**
 * Description of Hash
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Hash {
    
    const OUTPUT_SIZE = 32;
    
    private \HashContext $context;
    public function __construct(#[\SensitiveParameter] string $key) {
        $this->context = hash_init('sha256', HASH_HMAC, $key);
    }
    public function __invoke(string $data) : self {
        hash_update($this->context, $data);
        return $this;
    }
    public function __toString() : string {
        return hash_final($this->context, true);
    }
}
