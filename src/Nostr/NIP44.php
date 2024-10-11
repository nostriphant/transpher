<?php

namespace rikmeijer\Transpher\Nostr;

use \rikmeijer\Transpher\HashSHA256;
use rikmeijer\Transpher\Nostr\NIP44\Primitives;

/**
 * Description of NIP44
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class NIP44 {

    const HASH = 'sha256';

    static function hash(#[\SensitiveParameter] string $key): HashSHA256 {
        return (new HashSHA256($key));
    }

    static function hmac_digest(#[\SensitiveParameter] string $key, string $data): string {
        return self::hash($key)($data);
    }

    static function calcPaddedLength(int $length) {
        if ($length <= 32) {
            return 32;
        }
        $nextPower = 1 << ((int) floor(log($length - 1, 2))) + 1;
        $chunk = $nextPower <= 256 ? 32 : $nextPower / 8;
        return $chunk * ((int) floor(($length - 1) / $chunk) + 1);
    }
    
    static function pad(string $utf8_text) {
        $utf8_text_length = strlen($utf8_text);
        if ($utf8_text_length < 1) {
            throw new \InvalidArgumentException('text too short (< 1 char)');
        } elseif ($utf8_text_length > 65535) {
            throw new \InvalidArgumentException('text too long (> 65535 char)');
        }
        return Primitives::uInt16($utf8_text_length, true) . str_pad($utf8_text, self::calcPaddedLength($utf8_text_length), chr(0));
    }

    static function unpad(string $padded): bool|string {
        $expected_unpadded_length = Primitives::uInt16(substr($padded, 0, 2), true);
        $unpadded = substr($padded, 2, $expected_unpadded_length);
        $actual_unpadded_length = strlen($unpadded);
        if ($expected_unpadded_length === 0) {
            throw new \InvalidArgumentException('text too short (< 1 char)');
        } elseif ($expected_unpadded_length !== $actual_unpadded_length) {
            throw new \InvalidArgumentException('expected length mismatch');
        } elseif (2 + self::calcPaddedLength($actual_unpadded_length) !== strlen($padded)) {
            throw new \InvalidArgumentException('length re-calculation failed');
        }
        return $unpadded;
    }

    /* Based on: https://github.com/nbd-wtf/nostr-tools/blob/master/nip44.ts */

    static function encrypt(string $utf8_text, NIP44\MessageKeys $keys, string $salt): string {
        $padded = self::pad($utf8_text);
        $encrypter = new NIP44\Encrypter($keys, $salt);
        return sodium_bin2base64(Primitives::uInt8(2) . $encrypter($padded), SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    static function decrypt(string $payload, NIP44\MessageKeys $keys): bool|string {
        if ($payload === '') {
            throw new \InvalidArgumentException('empty payload');
        } elseif ($payload[0] === '#') {
            throw new \InvalidArgumentException('encryption version not supported');
        }

        $decoded = base64_decode($payload);
        $version = Primitives::uInt8(substr($decoded, 0, 1));
        if ($version !== 2) {
            throw new \InvalidArgumentException('encryption version not supported');
        }

        $salt = substr($decoded, 1, 32);
        $decrypter = new NIP44\Decrypter($keys, $salt);
        return self::unpad($decrypter($decoded));
    }
}
