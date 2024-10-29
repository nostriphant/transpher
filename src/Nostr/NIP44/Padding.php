<?php

namespace nostriphant\Transpher\Nostr\NIP44;
use nostriphant\Transpher\Nostr\NIP44\Primitives;

/**
 * Description of Pad
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Padding {

    static function calculateLength(int $length) {
        if ($length <= 32) {
            return 32;
        }
        $nextPower = 1 << ((int) floor(log($length - 1, 2))) + 1;
        $chunk = $nextPower <= 256 ? 32 : $nextPower / 8;
        return $chunk * ((int) floor(($length - 1) / $chunk) + 1);
    }
    
    static function add(string $utf8_text) : string {
        $utf8_text_length = strlen($utf8_text);
        if ($utf8_text_length < 1) {
            throw new \InvalidArgumentException('text too short (< 1 char)');
        } elseif ($utf8_text_length > 65535) {
            throw new \InvalidArgumentException('text too long (> 65535 char)');
        }
        return Primitives::uInt16($utf8_text_length, true) . str_pad($utf8_text, self::calculateLength($utf8_text_length), chr(0));
    }

    static function remove(string $padded) : string {
        $expected_unpadded_length = Primitives::uInt16(substr($padded, 0, 2), true);
        $unpadded = substr($padded, 2, $expected_unpadded_length);
        $actual_unpadded_length = strlen($unpadded);
        if ($expected_unpadded_length === 0) {
            throw new \InvalidArgumentException('text too short (< 1 char)');
        } elseif ($expected_unpadded_length !== $actual_unpadded_length) {
            throw new \InvalidArgumentException('expected length mismatch');
        } elseif (2 + self::calculateLength($actual_unpadded_length) !== strlen($padded)) {
            throw new \InvalidArgumentException('length re-calculation failed');
        }
        return $unpadded;
    }
}
