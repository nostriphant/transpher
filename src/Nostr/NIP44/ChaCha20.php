<?php

namespace nostriphant\Transpher\Nostr\NIP44;
use \phpseclib3\Crypt\ChaCha20 as libChaCha20;
/**
 * Description of ChaCha20
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class ChaCha20 {
    private libChaCha20 $cipher;
    public function __construct(#[\SensitiveParameter] string $key, string $nonce) {
        $this->cipher = new libChaCha20();
        $this->cipher->setKey($key);
        $this->cipher->setNonce($nonce);
    }
    
    public function __invoke(string $data): mixed {
        return $this->cipher->encrypt($data);
    }
}
