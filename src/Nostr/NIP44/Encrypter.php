<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace rikmeijer\Transpher\Nostr\NIP44;
use rikmeijer\Transpher\Nostr\NIP44;

/**
 * Description of Encrypter
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Encrypter {
    
    private ChaCha20 $chacha;
    private HMACAad $hmac;
    
    public function __construct(\rikmeijer\Transpher\Nostr\NIP44\MessageKeys $keys, private string $salt) {
        list($chacha_key, $chacha_nonce, $hmac_key) = iterator_to_array($keys($salt, 32, 12, 32));
    
        $this->chacha = new ChaCha20($chacha_key, $chacha_nonce);
        $this->hmac = new HMACAad(NIP44::hash($hmac_key), $this->salt);
    }
    
    public function __invoke(string $data): string {
        $ciphertext = ($this->chacha)($data);
        return $this->salt . $ciphertext . ($this->hmac)($ciphertext);
    }
}
