<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Transpher;
use Elliptic\EC;

/**
 * Description of Key
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Key {
    
    static function curve() : EC {
        return new EC('secp256k1');
    }
    
    static function generate(): callable
    {
        $ec = self::curve();
        $key = $ec->genKeyPair();
        return self::private($key->priv->toString('hex'));
    }
    
    static function private(string $private_key) : callable {
        return fn(callable $input) => $input($private_key);
    }
    
    static function signer(string $message) : callable {
        return fn(string $private_key) => (new \Mdanter\Ecc\Crypto\Signature\SchnorrSignature())->sign($private_key, $message)['signature'];
    }
    
    static function sharedSecret(string $recipient_pubkey) {
        return function(string $private_key) use ($recipient_pubkey) : bool|string {
            $ec = self::curve();
            try {
                $key1 = $ec->keyFromPrivate($private_key, 'hex');
                $pub2 = $ec->keyFromPublic($recipient_pubkey, 'hex')->pub;
                return $key1->derive($pub2)->toString('hex');
            } catch (\Exception $e) {
                return false;
            }
        };
        
    }
    
    static function public() : callable {
        return function(string $private_key): string {
            return self::curve()->keyFromPrivate($private_key)->getPublic(true, 'hex');
        };
    }
    
}
