<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Transpher;

/**
 * Description of Key
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Key {
    
    static function generate(): callable
    {
        $ec = new \Elliptic\EC('secp256k1');
        $key = $ec->genKeyPair();
        return self::private($key->priv->toString('hex'));
    }
    
    static function private(string $hex_private_key) : callable {
        return fn(callable $input) => match ($input) {
           null => self::getPublicFromPrivateKey($hex_private_key),
           default => $input($hex_private_key)
        };
    }
    
    static function signer(string $message) : callable {
        return fn(string $hex_private_key) => (new \Mdanter\Ecc\Crypto\Signature\SchnorrSignature())->sign($hex_private_key, $message)['signature'];
    }
    
    static function public() : callable {
        return [__CLASS__, 'getPublicFromPrivateKey'];
    }
    
    /**
     * Generate public key from private key as hex.
     *
     * @param string $private_hex
     *
     * @return string
     */
    static function getPublicFromPrivateKey(string $private_hex): string
    {
        $ec = new \Elliptic\EC('secp256k1');
        $private_key = $ec->keyFromPrivate($private_hex);
        $public_hex = $private_key->getPublic(true, 'hex');

        return $public_hex;
    }
}
