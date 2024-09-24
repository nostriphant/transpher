<?php

namespace Transpher\Nostr;

use \Transpher\HashSHA256;
use Transpher\Key;

/**
 * Description of NIP44
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class NIP44 {

    const HASH = 'sha256';
    const HASH_OUTPUT_SIZE = 32;

    static function hash(string $key): HashSHA256 {
        return (new HashSHA256($key));
    }

    static function hmac_digest(string $key, string $data): string {
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
    static function hkdf_expand(string $prk, string $info, int $length): string {
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

    static function getConversationKey(callable $private_key, string $pubkeyB): bool|string {
        try {
            $secret = $private_key(Key::sharedSecret(bin2hex($pubkeyB)));
        } catch (\Exception $e) {
            return false;
        }
        return self::hash('nip44-v2')($secret);
    }

    static function getMessageKeys(string $conversationKey, string $nonce): array {
        $keys = self::hkdf_expand($conversationKey, $nonce, 76);
        return [
            substr($keys, 0, 32),
            substr($keys, 32, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES),
            substr($keys, 44, 32)
        ];
    }

    static function hmacAad(string $key, string $aad, string $message): bool|string {
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

        // Content must be encoded from UTF-8 into byte array
        $decoded = $utf8_text;

        // Validate plaintext length. Minimum is 1 byte, maximum is 65535 bytes
        if (strlen($decoded) < 1) {
            return false;
        } elseif (strlen($decoded) > 65535) {
            return false;
        }

        // Padding algorithm is related to powers-of-two, with min padded msg size of 32
        $pad_length = self::calcPaddedLength($utf8_text_length);

        // Plaintext length is encoded in big-endian as first 2 bytes of the padded blob
        $unpaddedLengthBytes = self::uInt16($utf8_text_length, true);

        // Padding format is: [plaintext_length: u16][plaintext][zero_bytes]
        return $unpaddedLengthBytes . str_pad($decoded, $pad_length, chr(0));
    }

    static function unpad(string $padded): bool|string {
        $unpadded_length = self::uInt16(substr($padded, 0, 2), true);
        if ($unpadded_length === 0) {
            return false;
        }

        $unpadded = substr($padded, 2, $unpadded_length);
        if ($unpadded_length !== strlen($unpadded)) {
            return false;
        } elseif (2 + self::calcPaddedLength(strlen($unpadded)) !== strlen($padded)) {
            return false;
        }

        return $unpadded;
    }

    /* Based on: https://github.com/nbd-wtf/nostr-tools/blob/master/nip44.ts */

    static function encrypt(string $utf8_text, string $conversationKey, string $salt): false|string {
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
