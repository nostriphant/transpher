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
readonly class Key {
    
    public function __construct(private string $private_key) {}
    public function __invoke(callable $input): mixed {
        return $input($this->private_key);
    }
    static function fromHex(#[\SensitiveParameter] string $private_key) : callable {
        return new self($private_key);
    }
    
    static function curve() : EC {
        return new EC('secp256k1');
    }
    
    static function generate(): callable
    {
        $ec = self::curve();
        $key = $ec->genKeyPair();
        return self::fromHex($key->priv->toString('hex'));
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
            return substr(self::curve()->keyFromPrivate($private_key)->getPublic(true, 'hex'), 2);
        };
    }
    
}
