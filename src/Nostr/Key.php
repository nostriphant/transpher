<?php

namespace nostriphant\Transpher\Nostr;
use Elliptic\EC;

readonly class Key {

    public function __construct(#[\SensitiveParameter] private string $private_key) {
        
    }

    public function __invoke(callable $input): mixed {
        return $input($this->private_key);
    }

    static function fromHex(#[\SensitiveParameter] string $private_key): callable {
        return new self($private_key);
    }
    static function fromBech32(#[\SensitiveParameter] string $private_key) : callable {
        return new static(Bech32::fromNsec($private_key));
    }

    static function curve(): EC {
        return new EC('secp256k1');
    }

    static function generate(): callable {
        $ec = self::curve();
        $key = $ec->genKeyPair();
        return self::fromHex($key->priv->toString('hex'));
    }

    static function signer(string $hash): callable {
        return fn(string $private_key) => secp256k1_nostr_sign($private_key, $hash);
    }

    static function verify(string $pubkey, string $signature, string $hash): bool {
        return secp256k1_nostr_verify($pubkey, $hash, $signature);
    }

    static function sharedSecret(#[\SensitiveParameter] string $recipient_pubkey) {
        return function (#[\SensitiveParameter] string $private_key) use ($recipient_pubkey): bool|string {
            $ec = self::curve();
            try {
                $key1 = $ec->keyFromPrivate($private_key, 'hex');
                $pub2 = $ec->keyFromPublic($recipient_pubkey, 'hex')->pub;
                return $key1->derive($pub2)->toString('hex');
            } catch (\Exception $e) {
                throw new \InvalidArgumentException($e->getMessage());
            }
        };
    }

    static function public(Key\Format $format = Key\Format::HEXIDECIMAL): callable {
        return fn (#[\SensitiveParameter] string $private_key): string => match ($format) {
            Key\Format::BINARY => hex2bin(substr(self::curve()->keyFromPrivate($private_key)->getPublic(true, 'hex'), 2)),
            Key\Format::BECH32 => Bech32::toNpub(substr(self::curve()->keyFromPrivate($private_key)->getPublic(true, 'hex'), 2)),
            Key\Format::HEXIDECIMAL => substr(self::curve()->keyFromPrivate($private_key)->getPublic(true, 'hex'), 2),
                };
    }
    static function private(Key\Format $format = Key\Format::HEXIDECIMAL): callable {
        return fn (#[\SensitiveParameter] string $private_key): string => match ($format) {
            Key\Format::BINARY => hex2bin($private_key),
            Key\Format::BECH32 => Bech32::toNsec($private_key),
                    Key\Format::HEXIDECIMAL => $private_key,
        };
    }
}
