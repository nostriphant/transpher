<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Transpher\Nostr;

use Elliptic\EC;

/**
 * Description of NIP44
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class NIP44 {
    
    static function hmac_digest(string $key, string $data) : string {
        return hash_hmac('sha256', $data, $key, false);
    }
    static function hkdf_extract(string $ikm, string $salt) : string {
        return self::hmac_digest($salt, $ikm);
    }
    
    static function getSharedSecret(string $privkeyA, string $pubkeyB) : string {
        $ec = new EC('secp256k1');
        $key1 = $ec->keyFromPrivate($privkeyA);
        return $key1->derive($ec->keyFromPublic(hex2bin($pubkeyB))->pub)->toString('hex');
    }
    
    static function getConversationKey(string $privkeyA, string $pubkeyB) : string {
        return self::hkdf_extract(hex2bin(self::getSharedSecret($privkeyA, '02'.$pubkeyB)), 'nip44-v2');
        
    }
}
