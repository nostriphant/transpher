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
    
    static function private(string $private_key) : callable {
        return fn(callable $input) => $input($private_key);
    }
    
    static function signer(string $message) : callable {
        return fn(string $private_key) => (new \Mdanter\Ecc\Crypto\Signature\SchnorrSignature())->sign($private_key, $message)['signature'];
    }
    
    static function public() : callable {
        return function(string $private_key): string {
            $ec = new \Elliptic\EC('secp256k1');
            $private_key = $ec->keyFromPrivate($private_key);
            $public_hex = $private_key->getPublic(true, 'hex');
            return $public_hex;
        };
    }
    
    static function conversation(string $recipient_pubkey) : callable {
        return function(string $hex_private_key) use ($recipient_pubkey) : string {
            $key = Nostr\NIP44::getConversationKey(hex2bin($hex_private_key), hex2bin($recipient_pubkey));
            if ($key === false) {
                throw new \Exception('Unable to determine conversation key');
            }
            return $key;
        };
    }
    
}
