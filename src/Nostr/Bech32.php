<?php

namespace rikmeijer\Transpher\Nostr;

use function BitWasp\Bech32\convertBits;
use function BitWasp\Bech32\decode;
use function BitWasp\Bech32\encode;

class Bech32 {


    private static function convertBech32ToHex(#[\SensitiveParameter] string $bech32_key): string {
        $str = '';
        try {
            $decoded = decode($bech32_key);
            $data = $decoded[1];
            $bytes = convertBits($data, count($data), 5, 8, false);
            foreach ($bytes as $item) {
                $str .= str_pad(dechex($item), 2, '0', STR_PAD_LEFT);
            }
        } catch (Bech32Exception) {

        }

        return $str;
    }

    private static function convertHexToBech32(#[\SensitiveParameter] string $hex_key, string $prefix) {
        $str = '';

        try {
            $dec = [];
            $split = str_split($hex_key, 2);
            foreach ($split as $item) {
                $dec[] = hexdec($item);
            }
            $bytes = convertBits($dec, count($dec), 8, 5);
            $str = encode($prefix, $bytes);
        } catch (Bech32Exception) {

        }

        return $str;
    }

    static function toNpub(string $hex) {
        return self::convertHexToBech32($hex, 'npub');
    }

    static function fromNpub(string $npub) {
        return self::convertBech32ToHex($npub, 'npub');
    }


    static function toNsec(#[\SensitiveParameter] string $hex): string {
        return self::convertHexToBech32($hex, 'nsec');
    }

    static function fromNsec(#[\SensitiveParameter] string $nsec): string {
        return self::convertBech32ToHex($nsec, 'nsec');
    }
}
