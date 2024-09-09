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
        return fn(?string $input = null) => match ($input) {
           null => Message::getPublicFromPrivateKey($hex_private_key),
           default => (new \Mdanter\Ecc\Crypto\Signature\SchnorrSignature())->sign($hex_private_key, $input)['signature']
        };
    }
}
