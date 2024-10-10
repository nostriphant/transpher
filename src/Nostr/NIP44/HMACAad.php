<?php

namespace rikmeijer\Transpher\Nostr\NIP44;
use rikmeijer\Transpher\HashSHA256;

/**
 * Description of HMACAad
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class HMACAad {
    
    public function __construct(#[\SensitiveParameter] private HashSHA256 $hash, private string $aad) {
    }
    
    public function __invoke(string $data): string {
        return ($this->hash)($this->aad . $data);
    }
}
