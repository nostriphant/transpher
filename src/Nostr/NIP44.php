<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Transpher\Nostr;

use Elliptic\EC;

/**
 * Description of NIP44
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class NIP44 {
    
    const HASH = 'sha256';
    const HASH_OUTPUTLEN = 32;

    static function hmac_digest(string $key, string $data): string {
        return hash_hmac(self::HASH, $data, $key, true);
    }
    
    static function hkdf_extract(string $ikm, string $salt): string {
        return self::hmac_digest($salt, $ikm);
    }
    
    const HASH_OUTPUT_SIZE = 32;
    
    /**
     * Based on https://github.com/mgp25/libsignal-php/blob/master/src/kdf/HKDF.php
     * @param string $prk
     * @param string $info
     * @param int $length
     * @return string
     * 
     */
    static function hkdf_expand(string $prk, string $info, int $length) : string {
        $iterations = (int) ceil(floatval($length) / floatval(self::HASH_OUTPUT_SIZE));
        $remainingBytes = $length;
        $mixin = '';
        $result = '';
        for ($i = 1; $i < $iterations + 1; $i++) {
            $mac = hash_init('sha256', HASH_HMAC, $prk);
            hash_update($mac, $mixin);
            if ($info != null) {
                hash_update($mac, $info);
            }
            $updateChr = chr($i % 256);
            hash_update($mac, $updateChr);
            $stepResult = hash_final($mac, true);
            $stepSize = min($remainingBytes, strlen($stepResult));
            $result .= substr($stepResult, 0, $stepSize);
            $mixin = $stepResult;
            $remainingBytes -= $stepSize;
        }

        return $result;
    }
    
    static function hkdf(string $ikm, string $salt, string $info, int $length) : string {
        return hash_hkdf(self::HASH, $ikm, $length, $info, $salt);
    }
    
    static function getSharedSecret(string $privkeyA, string $pubkeyB): string {
        $ec = new EC('secp256k1');
        $key1 = $ec->keyFromPrivate($privkeyA, 'hex');
        $pub2 = $ec->keyFromPublic($pubkeyB, 'hex')->pub;
        return $key1->derive($pub2)->toString('hex');
    }

    static function getConversationKey(string $privkeyA, string $pubkeyB): bool|string {
        try {
            $secret = self::getSharedSecret($privkeyA, $pubkeyB);
        } catch (\Exception $e) {
            return false;
        }
        return self::hkdf_extract(hex2bin($secret), 'nip44-v2');
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
        return self::hmac_digest($key, $aad . $message);
        

    } 
    
    static function calcPaddedLength(int $length) {
        if ($length <= 32) {
            return 32;
        }
        $nextPower = 1 << ((int)floor(log($length - 1, 2))) + 1;
        $chunk = $nextPower <= 256 ? 32 : $nextPower / 8;
        return $chunk * ((int)floor(($length - 1) / $chunk) + 1);
    }
    
    static function chacha20(string $key, string $nonce, string $data) : string {
        $cipher = new \phpseclib3\Crypt\ChaCha20();
        $cipher->setKey($key);
        $cipher->setNonce($nonce);
        //$cipher->setCounter(self::uInt32(0, true));
        return $cipher->encrypt($data);
    }
    

    public static function uInt8($i) {
        return is_int($i) ? pack("C", $i) : unpack("C", $i)[1];
    }

    public static function uInt16($i, $endianness=false) {
        $f = is_int($i) ? "pack" : "unpack";

        if ($endianness === true) {  // big-endian
            $i = $f("n", $i);
        }
        else if ($endianness === false) {  // little-endian
            $i = $f("v", $i);
        }
        else if ($endianness === null) {  // machine byte order
            $i = $f("S", $i);
        }

        return is_array($i) ? $i[1] : $i;
    }
    
    public static function uInt32($i, $endianness=false) {
        $f = is_int($i) ? "pack" : "unpack";

        if ($endianness === true) {  // big-endian
            $i = $f("N", $i);
        }
        else if ($endianness === false) {  // little-endian
            $i = $f("V", $i);
        }
        else if ($endianness === null) {  // machine byte order
            $i = $f("L", $i);
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
    
    static function unpad(string $padded) : bool|string {
        $unpadded_length = self::uInt16(substr($padded, 0, 2), true);
        if ($unpadded_length === 0) {
            return false;
        }
        
        $unpadded =  substr($padded, 2, $unpadded_length);
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
