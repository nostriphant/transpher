<?php

namespace rikmeijer\Transpher\Nostr;

use \rikmeijer\Transpher\HashSHA256;
use rikmeijer\Transpher\Key;

/**
 * Description of NIP44
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class NIP44 {

    const HASH = 'sha256';
    const HASH_OUTPUT_SIZE = 32;

    static function hash(#[\SensitiveParameter] string $key): HashSHA256 {
        return (new HashSHA256($key));
    }

    static function hmac_digest(#[\SensitiveParameter] string $key, string $data): string {
        return self::hash($key)($data);
    }

    /**
     * Based on https://github.com/mgp25/libsignal-php/blob/master/src/kdf/HKDF.php
     * @param string $prk
     * @param string $info
     * @param int $length
     * @return string
     * 
     */
    static function hkdf_expand(#[\SensitiveParameter] string $prk, string $info, int $length): string {
        $iterations = (int) ceil($length / self::HASH_OUTPUT_SIZE);
        $stepResult = '';
        $result = '';
        for ($i = 0; $i < $iterations; $i++) {
            $stepResult = (string) self::hash($prk)($stepResult)($info)(chr(($i + 1) % 256));
            $stepSize = min($length, strlen($stepResult));
            $result .= substr($stepResult, 0, $stepSize);
            $length -= $stepSize;
        }

        return $result;
    }

    static function getConversationKey(#[\SensitiveParameter] Key $private_key, string $pubkeyB): bool|string {
        if (false === ($secret = $private_key(Key::sharedSecret('02' . bin2hex($pubkeyB))))) {
            return false;
        }
        return self::hash('nip44-v2')(hex2bin($secret));
    }

    static function getMessageKeys(#[\SensitiveParameter] string $conversationKey, string $nonce): array {
        $keys = new NIP44\MessageKeys($conversationKey);
        return iterator_to_array($keys($nonce, 32, 12, 32));
    }

    static function hmacAad(#[\SensitiveParameter] string $key, string $aad, string $message): bool|string {
        if (strlen($aad) !== 32) {
            return false;
        }
        return self::hash($key)($aad . $message);
    }

    static function calcPaddedLength(int $length) {
        if ($length <= 32) {
            return 32;
        }
        $nextPower = 1 << ((int) floor(log($length - 1, 2))) + 1;
        $chunk = $nextPower <= 256 ? 32 : $nextPower / 8;
        return $chunk * ((int) floor(($length - 1) / $chunk) + 1);
    }

    static function chacha20(string $key, string $nonce, string $data): string {
        $cipher = new \phpseclib3\Crypt\ChaCha20();
        $cipher->setKey($key);
        $cipher->setNonce($nonce);
        return $cipher->encrypt($data);
    }

    public static function uInt8($i) {
        return is_int($i) ? pack("C", $i) : unpack("C", $i)[1];
    }

    public static function uInt16($i, $endianness = false) {
        $f = is_int($i) ? "pack" : "unpack";

        if ($endianness === true) {  // big-endian
            $i = $f("n", $i);
        } else if ($endianness === false) {  // little-endian
            $i = $f("v", $i);
        } else if ($endianness === null) {  // machine byte order
            $i = $f("S", $i);
        }

        return is_array($i) ? $i[1] : $i;
    }

    static function pad(string $utf8_text) {
        $utf8_text_length = strlen($utf8_text);
        if ($utf8_text_length < 1) {
            return false;
        } elseif ($utf8_text_length > 65535) {
            return false;
        }
        return self::uInt16($utf8_text_length, true) . str_pad($utf8_text, self::calcPaddedLength($utf8_text_length), chr(0));
    }

    static function unpad(string $padded): bool|string {
        $expected_unpadded_length = self::uInt16(substr($padded, 0, 2), true);
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

    static function encrypt(string $utf8_text, #[\SensitiveParameter] string $conversationKey, string $salt): false|string {
        $padded = self::pad($utf8_text);
        if ($padded === false) {
            return false;
        }
        list($chacha_key, $chacha_nonce, $hmac_key) = self::getMessageKeys($conversationKey, $salt);
        $ciphertext = self::chacha20($chacha_key, $chacha_nonce, $padded);
        return sodium_bin2base64(self::uInt8(2) . $salt . $ciphertext . self::hmacAad($hmac_key, $salt, $ciphertext), SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    static function decrypt(string $payload, string $conversationKey): bool|string {
        if ($payload === '') {
            return false;
        } elseif ($payload[0] === '#') {
            return false;
        }

        $decoded = base64_decode($payload);
        $version = self::uInt8(substr($decoded, 0, 1));
        if ($version !== 2) {
            return false;
        }

        $salt = substr($decoded, 1, 32);
        $ciphertext = substr($decoded, 33, -32);
        $mac = substr($decoded, -32);

        list($chacha_key, $chacha_nonce, $hmac_key) = self::getMessageKeys($conversationKey, $salt);
        $expected_mac = self::hmacAad($hmac_key, $salt, $ciphertext);
        if ($mac !== $expected_mac) {
            return false;
        }

        $padded = self::chacha20($chacha_key, $chacha_nonce, $ciphertext);
        return self::unpad($padded);
    }
}
