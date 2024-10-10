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
            return false;
        } elseif ($utf8_text_length > 65535) {
            return false;
        }
        return Primitives::uInt16($utf8_text_length, true) . str_pad($utf8_text, self::calcPaddedLength($utf8_text_length), chr(0));
    }

    static function unpad(string $padded): bool|string {
        $expected_unpadded_length = Primitives::uInt16(substr($padded, 0, 2), true);
        $unpadded = substr($padded, 2, $expected_unpadded_length);
        $actual_unpadded_length = strlen($unpadded);
        if ($expected_unpadded_length === 0) {
            return false;
        } elseif ($expected_unpadded_length !== $actual_unpadded_length) {
            return false;
        } elseif (2 + self::calcPaddedLength($actual_unpadded_length) !== strlen($padded)) {
            return false;
        }
        return $unpadded;
    }

    /* Based on: https://github.com/nbd-wtf/nostr-tools/blob/master/nip44.ts */

    static function encrypt(string $utf8_text, NIP44\MessageKeys $keys, string $salt): false|string {
        $padded = self::pad($utf8_text);
        if ($padded === false) {
            return false;
        } elseif (strlen($salt) !== 32) {
            return false;
        }
        
        list($chacha_key, $chacha_nonce, $hmac_key) = iterator_to_array($keys($salt, 32, 12, 32));
        $ciphertext = (new NIP44\ChaCha20($chacha_key, $chacha_nonce))($padded);
        $hmac = new NIP44\HMACAad(self::hash($hmac_key), $salt);
        return sodium_bin2base64(Primitives::uInt8(2) . $salt . $ciphertext . $hmac($ciphertext), SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    static function decrypt(string $payload, NIP44\MessageKeys $keys): bool|string {
        if ($payload === '') {
            return false;
        } elseif ($payload[0] === '#') {
            return false;
        }

        $decoded = base64_decode($payload);
        $version = Primitives::uInt8(substr($decoded, 0, 1));
        if ($version !== 2) {
            return false;
        }

        $salt = substr($decoded, 1, 32);
        $ciphertext = substr($decoded, 33, -32);
        $mac = substr($decoded, -32);

        list($chacha_key, $chacha_nonce, $hmac_key) = iterator_to_array($keys($salt, 32, 12, 32));
        $hmac = new NIP44\HMACAad(self::hash($hmac_key), $salt);
        if ($mac !== $hmac($ciphertext)) {
            return false;
        }

        $padded = (new NIP44\ChaCha20($chacha_key, $chacha_nonce))($ciphertext);
        return self::unpad($padded);
    }
}
