<?php

namespace rikmeijer\Transpher\Nostr\NIP44;
use rikmeijer\Transpher\HashSHA256;
use rikmeijer\Transpher\Nostr\NIP44;
/**
 * Description of MessageKeys
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class MessageKeys {
    
    
    public function __construct(#[\SensitiveParameter] private string $conversation_key) {
    }

    /**
     * Based on https://github.com/mgp25/libsignal-php/blob/master/src/kdf/HKDF.php
     * @param string $prk
     * @param string $info
     * @param int $length
     * @return string
     * 
     */
    private function hkdf_expand(string $info, int $length): string {
        $iterations = (int) ceil($length / HashSHA256::OUTPUT_SIZE);
        $stepResult = '';
        $result = '';
        for ($i = 0; $i < $iterations; $i++) {
            $stepResult = (string) NIP44::hash($this->conversation_key)($stepResult)($info)(chr(($i + 1) % 256));
            $stepSize = min($length, strlen($stepResult));
            $result .= substr($stepResult, 0, $stepSize);
            $length -= $stepSize;
        }
        return $result;
    }
    
    public function __invoke(string $salt, int ...$lengths) : \Generator {
        $keys = $this->hkdf_expand($salt, array_sum($lengths));
        $offset = 0;
        foreach ($lengths as $length) {
            yield substr($keys, $offset, $length);
            $offset += $length;
        }
    }
    
}
